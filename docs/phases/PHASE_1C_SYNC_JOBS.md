# Phase 1C: Sync Jobs

> **Goal:** Create queued jobs for syncing circuits and computing aggregates.
> **Estimated Time:** 2 days
> **Dependencies:** Phase 1B complete (API service layer)

---

## Status: Not Started

| Item | Status | Notes |
|------|--------|-------|
| SyncCircuitsJob | Needed | Sync circuit list from API |
| SyncCircuitAggregatesJob | Needed | Fetch planned units, compute aggregates |
| CreateDailySnapshotsJob | Needed | Daily snapshot creation |
| Scheduled tasks | Needed | Console schedule configuration |
| Broadcast events | Needed | Real-time sync notifications |

---

## Sync Schedule

| Status | Frequency | Schedule |
|--------|-----------|----------|
| `ACTIV` | Twice daily | 4:30 AM and 4:30 PM ET |
| `QC` | Weekly | Monday 4:30 AM ET |
| `REWORK` | Weekly | Monday 4:30 AM ET |
| `CLOSE` | Weekly | Monday 4:30 AM ET |

**Important:** All syncs weekdays only (Mon-Fri).

---

## Checklist

### Jobs
- [ ] Create SyncCircuitsJob
  - Status filtering (ACTIV, QC, REWORK, CLOSE)
  - Upsert by job_guid
  - Planner linkage (circuit_user pivot)
  - Error handling with retry
  - Sync log creation
  - Rate limiting (pause every 5 calls)
- [ ] Create SyncCircuitAggregatesJob
  - Fetch planned units by WO#
  - Compute aggregates (NOT store individual units)
  - Update circuit_aggregates table
  - Handle credential selection
- [ ] Create CreateDailySnapshotsJob
  - Snapshot all non-closed circuits
  - Skip closed circuits

### Events
- [ ] Create SyncStartedEvent
- [ ] Create SyncCompletedEvent
- [ ] Create SyncFailedEvent
- [ ] Create PlannedUnitsSyncedEvent
- [ ] Create CircuitWorkflowChangedEvent
- [ ] Create CircuitUpdatedEvent

### Listeners
- [ ] Create LogSyncStarted listener
- [ ] Create LogSyncCompleted listener
- [ ] Create BroadcastSyncStatus listener

### Configuration
- [ ] Configure schedule in `routes/console.php`
- [ ] Configure broadcast channels in `routes/channels.php`
- [ ] Register events in EventServiceProvider (if needed)

### Tests
- [ ] Write feature tests for SyncCircuitsJob
- [ ] Write feature tests for SyncCircuitAggregatesJob
- [ ] Write feature tests for CreateDailySnapshotsJob
- [ ] Test with mock API responses

---

## File Structure

```
app/
├── Jobs/
│   ├── SyncCircuitsJob.php
│   ├── SyncCircuitAggregatesJob.php
│   └── CreateDailySnapshotsJob.php
├── Events/
│   ├── SyncStartedEvent.php
│   ├── SyncCompletedEvent.php
│   ├── SyncFailedEvent.php
│   ├── PlannedUnitsSyncedEvent.php
│   ├── CircuitWorkflowChangedEvent.php
│   └── CircuitUpdatedEvent.php
└── Listeners/
    ├── LogSyncStarted.php
    ├── LogSyncCompleted.php
    └── BroadcastSyncStatus.php

routes/
├── console.php                 # Schedule definitions
└── channels.php                # Broadcast channel auth
```

---

## Existing Model Helpers

These models already exist with helper methods that **must be used** during implementation:

### SyncLog Model
| Method | Purpose |
|--------|---------|
| `SyncLog::start(type, trigger, regionId?, apiStatusFilter?, triggeredBy?, context?)` | Create a new sync log with `sync_status = Started` |
| `$syncLog->complete(['circuits_processed' => n, ...])` | Mark sync as completed, auto-calculates duration |
| `$syncLog->fail($message, $details?)` | Mark sync as failed |
| `$syncLog->completeWithWarning($message, $results)` | Mark sync as completed with warning |

**Important field names:** `sync_status`, `sync_trigger` (not `status`, `triggered_by` enum)

### Circuit Model
| Method | Purpose |
|--------|---------|
| `$circuit->getOrCreateUiState()` | Get or create UI state for a circuit |
| `$circuit->scopeNeedsSync($query)` | Scope for circuits needing aggregate sync |

### User Model
| Field | Purpose |
|-------|---------|
| `ws_username` | WorkStudio username (use this for planner matching) |
| `ws_user_guid` | WorkStudio GUID |
| `is_ws_linked` | Whether user is linked to WorkStudio |

### UnlinkedPlanner Model
| Field | Purpose |
|-------|---------|
| `ws_username` | WorkStudio username (NOT `api_username`) |
| `linkToUser($user)` | Link this planner to a user |

### CircuitTransformer
| Method | Purpose |
|--------|---------|
| `transformCollection($response)` | Transform API response to array of circuit data |
| `extractPlanners($circuitData)` | Extract planner usernames from circuit |

---

## Key Implementation Details

### SyncCircuitsJob

```php
<?php

namespace App\Jobs;

use App\Models\Circuit;
use App\Models\SyncLog;
use App\Models\Region;
use App\Models\UnlinkedPlanner;
use App\Services\WorkStudio\WorkStudioApiService;
use App\Services\WorkStudio\Transformers\CircuitTransformer;
use App\Enums\SyncType;
use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCircuitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private array $statuses,
        private ?int $triggeredByUserId = null,
        private SyncTrigger $triggerType = SyncTrigger::Scheduled
    ) {}

    public function handle(
        WorkStudioApiService $api,
        CircuitTransformer $transformer
    ): void {
        // Use SyncLog::start() helper - it sets sync_status, sync_trigger, started_at
        $syncLog = SyncLog::start(
            type: SyncType::CircuitList,
            trigger: $this->triggerType,
            apiStatusFilter: implode(',', $this->statuses),
            triggeredBy: $this->triggeredByUserId
        );

        try {
            if (!$api->healthCheck()) {
                throw new \Exception('WorkStudio API is unavailable');
            }

            $processedCount = 0;

            foreach ($this->statuses as $status) {
                $response = $api->getViewData(
                    config('workstudio.views.vegetation_assessments'),
                    [
                        'FilterName' => 'By Job Status',
                        'FilterValue' => $status,
                        'PersistFilter' => true,
                    ]
                );

                $circuits = $transformer->transformCollection($response);

                foreach ($circuits as $circuitData) {
                    $this->upsertCircuit($circuitData);
                    $processedCount++;

                    // Rate limiting: pause every 5 circuits
                    if ($processedCount % config('workstudio.sync.calls_before_delay', 5) === 0) {
                        usleep(config('workstudio.sync.rate_limit_delay', 500000));
                    }
                }
            }

            // Create daily snapshots for non-closed circuits
            dispatch(new CreateDailySnapshotsJob());

            // Use complete() helper - handles sync_status, completed_at, duration_seconds
            $syncLog->complete([
                'circuits_processed' => $processedCount,
            ]);

            event(new \App\Events\SyncCompletedEvent($syncLog));

        } catch (\Exception $e) {
            // Use fail() helper - handles sync_status, completed_at, duration_seconds
            $syncLog->fail($e->getMessage());

            event(new \App\Events\SyncFailedEvent($syncLog, $e));

            throw $e;
        }
    }

    private function upsertCircuit(array $data): void
    {
        $circuit = Circuit::updateOrCreate(
            ['job_guid' => $data['job_guid']],
            $data
        );

        // Use getOrCreateUiState() helper on Circuit model
        if ($circuit->wasRecentlyCreated) {
            $circuit->getOrCreateUiState();
        }

        // Handle planner linkage (ws_username is the field name in User model)
        $this->linkPlanner($circuit, $data['planner_username'] ?? null);
    }

    private function linkPlanner(Circuit $circuit, ?string $wsUsername): void
    {
        if (empty($wsUsername)) {
            return;
        }

        // Match by ws_username (WorkStudio username field on User model)
        $user = \App\Models\User::where('ws_username', $wsUsername)->first();

        if ($user) {
            $circuit->planners()->syncWithoutDetaching([
                $user->id => ['assignment_source' => \App\Enums\AssignmentSource::ApiSync],
            ]);
        } else {
            // Track unlinked planners for later manual linking
            // Uses ws_username field (not api_username)
            UnlinkedPlanner::updateOrCreate(
                ['ws_username' => $wsUsername],
                [
                    'last_seen_at' => now(),
                    'occurrence_count' => \DB::raw('occurrence_count + 1'),
                ]
            );
        }
    }
}
```

### Schedule Configuration

```php
// routes/console.php

use App\Jobs\SyncCircuitsJob;
use App\Jobs\CreateDailySnapshotsJob;
use Illuminate\Support\Facades\Schedule;

// ACTIV - twice daily on weekdays (Eastern Time)
Schedule::job(new SyncCircuitsJob(['ACTIV']))
    ->weekdays()
    ->timezone('America/New_York')
    ->at('04:30')
    ->name('sync-circuits-activ-morning')
    ->withoutOverlapping();

Schedule::job(new SyncCircuitsJob(['ACTIV']))
    ->weekdays()
    ->timezone('America/New_York')
    ->at('16:30')
    ->name('sync-circuits-activ-afternoon')
    ->withoutOverlapping();

// QC, REWORK, CLOSE - weekly on Monday (Monday is already a weekday)
Schedule::job(new SyncCircuitsJob(['QC', 'REWRK', 'CLOSE']))
    ->mondays()
    ->timezone('America/New_York')
    ->at('04:30')
    ->name('sync-circuits-weekly')
    ->withoutOverlapping();

// Daily snapshots - 5:00 AM ET (after sync completes)
Schedule::job(new CreateDailySnapshotsJob())
    ->weekdays()
    ->timezone('America/New_York')
    ->at('05:00')
    ->name('create-daily-snapshots')
    ->withoutOverlapping();
```

### Broadcast Channels

```php
// routes/channels.php

use Illuminate\Support\Facades\Broadcast;

// All authenticated users can listen to dashboard
Broadcast::channel('dashboard', fn ($user) => true);

// Circuit-specific - check user can view this circuit
Broadcast::channel('circuit.{circuitId}', function ($user, $circuitId) {
    return $user->canViewCircuit($circuitId);
});

// Admin-only sync status channel
Broadcast::channel('sync-status', fn ($user) => $user->hasAnyRole(['sudo_admin', 'admin']));
```

### Events Example

```php
// app/Events/SyncCompletedEvent.php

<?php

namespace App\Events;

use App\Models\SyncLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncCompletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SyncLog $syncLog
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('dashboard'),
            new Channel('sync-status'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sync.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'sync_id' => $this->syncLog->id,
            'circuits_processed' => $this->syncLog->circuits_processed,
            'completed_at' => $this->syncLog->completed_at->toIso8601String(),
        ];
    }
}
```

---

## Sync Flow Diagram

```
1. Create sync_log (status: started)

2. Health check: ping base URL
   └── If down → log warning, exit

3. FOR EACH enabled status:
   │
   │  RETRY WRAPPER (max 5 attempts, exponential backoff):
   │  ├── Call GETVIEWDATA with status filter
   │  ├── Delay after every 5 consecutive calls
   │  ├── If fail → retry up to 5x
   │  └── If still fails → ABORT, log error, exit
   │
   └── FOR EACH circuit in response:
       │
       ├── Lookup by job_guid
       │   ├── EXISTS: Update circuit data
       │   └── NEW: Insert circuit + circuit_ui_state
       │
       ├── Sync planner → circuit_user pivot
       │   └── If no matching user → upsert unlinked_planners
       │
       └── If workflow_stage = 'pending_permissions':
           └── Queue: SyncCircuitAggregatesJob

4. Dispatch: CreateDailySnapshotsJob

5. Update sync_log (status: completed)

6. Broadcast: SyncCompletedEvent
```

---

## Testing Requirements

```php
// tests/Feature/Sync/SyncCircuitsJobTest.php
it('creates new circuits from API response', function () {
    Http::fake([
        '*' => Http::response([
            'DataSet' => [
                'Heading' => ['SS_JOBGUID', 'SS_WO', 'SS_TITLE', ...],
                'Data' => [
                    ['guid-1', '2025-1234', 'Test Circuit', ...],
                ],
            ],
        ]),
    ]);

    (new SyncCircuitsJob(['ACTIV']))->handle(
        app(WorkStudioApiService::class),
        app(CircuitTransformer::class)
    );

    expect(Circuit::where('job_guid', 'guid-1')->exists())->toBeTrue();
});

it('updates existing circuits by job_guid', function () {...});

it('creates ui state for new circuits', function () {...});

it('logs sync completion', function () {
    // ... run job ...

    // Use sync_status field (not status)
    expect(SyncLog::where('sync_status', SyncStatus::Completed)->exists())->toBeTrue();
});

it('handles API failures gracefully', function () {
    Http::fake([
        '*' => Http::response('Server Error', 500),
    ]);

    expect(fn () => (new SyncCircuitsJob(['ACTIV']))->handle(...))
        ->toThrow(\Exception::class);

    // Use sync_status field (not status)
    expect(SyncLog::where('sync_status', SyncStatus::Failed)->exists())->toBeTrue();
});

// tests/Feature/Sync/SyncCircuitAggregatesJobTest.php
it('computes aggregates from planned units', function () {...});
it('updates circuit_aggregates table', function () {...});
it('tracks permission status counts', function () {...});

// tests/Feature/Sync/CreateDailySnapshotsJobTest.php
it('creates snapshots for non-closed circuits', function () {
    $circuit = Circuit::factory()->create(['api_status' => 'ACTIV']);

    (new CreateDailySnapshotsJob())->handle();

    expect($circuit->snapshots()->where('snapshot_date', today())->exists())->toBeTrue();
});

it('skips closed circuits', function () {
    $circuit = Circuit::factory()->create(['api_status' => 'CLOSE']);

    (new CreateDailySnapshotsJob())->handle();

    expect($circuit->snapshots()->count())->toBe(0);
});
```

---

## Next Phase

Once all items are checked, proceed to **[Phase 1D: Theme System](./PHASE_1D_THEME_SYSTEM.md)**.

**Note:** Phase 1D can run in parallel with Phase 1C since they don't depend on each other.
