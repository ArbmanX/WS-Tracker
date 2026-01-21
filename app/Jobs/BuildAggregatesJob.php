<?php

namespace App\Jobs;

use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Models\PlannerDailyAggregate;
use App\Models\PlannerWeeklyAggregate;
use App\Models\RegionalDailyAggregate;
use App\Models\RegionalWeeklyAggregate;
use App\Models\SyncLog;
use App\Services\Sync\SyncOutputLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job for building daily and weekly aggregate rollups.
 *
 * This job can be triggered manually from the admin panel to rebuild
 * aggregates for specific date ranges. Useful for:
 * - Rebuilding after data corrections
 * - Generating historical aggregates
 * - Manually triggering scheduled aggregate builds
 */
class BuildAggregatesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds before the job should timeout.
     */
    public int $timeout = 900; // 15 minutes

    /**
     * Aggregate type constants.
     */
    public const TYPE_DAILY = 'daily';

    public const TYPE_WEEKLY = 'weekly';

    public const TYPE_BOTH = 'both';

    /**
     * Create a new job instance.
     *
     * @param  string  $aggregateType  Type of aggregates to build (daily, weekly, both)
     * @param  Carbon|null  $targetDate  Target date for aggregates (null for today)
     * @param  SyncTrigger  $triggerType  What triggered this build
     * @param  int|null  $triggeredByUserId  User who triggered the build (if manual)
     * @param  string|null  $outputLoggerKey  Key for SyncOutputLogger (for live progress)
     */
    public function __construct(
        private string $aggregateType = self::TYPE_BOTH,
        private ?Carbon $targetDate = null,
        private SyncTrigger $triggerType = SyncTrigger::Manual,
        private ?int $triggeredByUserId = null,
        private ?string $outputLoggerKey = null,
    ) {
        $this->targetDate = $targetDate ?? now();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Initialize output logger if key provided
        $outputLogger = $this->outputLoggerKey
            ? new SyncOutputLogger($this->outputLoggerKey)
            : null;

        $outputLogger?->start("Building {$this->aggregateType} aggregates for {$this->targetDate->format('Y-m-d')}...");

        // Start sync log
        $syncLog = SyncLog::start(
            type: SyncType::Aggregates,
            trigger: $this->triggerType,
            triggeredBy: $this->triggeredByUserId,
            context: [
                'aggregate_type' => $this->aggregateType,
                'target_date' => $this->targetDate->toDateString(),
            ],
        );

        $outputLogger?->info('Sync log ID: '.$syncLog->id);

        try {
            $results = [
                'daily_planner_aggregates' => 0,
                'daily_regional_aggregates' => 0,
                'weekly_planner_aggregates' => 0,
                'weekly_regional_aggregates' => 0,
                'errors' => [],
            ];

            // Build daily aggregates
            if (in_array($this->aggregateType, [self::TYPE_DAILY, self::TYPE_BOTH])) {
                $outputLogger?->info('Building daily aggregates...');
                $dailyResults = $this->buildDailyAggregates($outputLogger);
                $results['daily_planner_aggregates'] = $dailyResults['planner'];
                $results['daily_regional_aggregates'] = $dailyResults['regional'];
                $results['errors'] = array_merge($results['errors'], $dailyResults['errors']);
            }

            // Build weekly aggregates
            if (in_array($this->aggregateType, [self::TYPE_WEEKLY, self::TYPE_BOTH])) {
                $outputLogger?->info('Building weekly aggregates...');
                $weeklyResults = $this->buildWeeklyAggregates($outputLogger);
                $results['weekly_planner_aggregates'] = $weeklyResults['planner'];
                $results['weekly_regional_aggregates'] = $weeklyResults['regional'];
                $results['errors'] = array_merge($results['errors'], $weeklyResults['errors']);
            }

            // Complete sync log
            if (! empty($results['errors'])) {
                $syncLog->completeWithWarning(
                    count($results['errors']).' errors during aggregate build',
                    $results
                );
            } else {
                $syncLog->complete($results);
            }

            $summaryMessage = sprintf(
                'Aggregates built: %d daily planner, %d daily regional, %d weekly planner, %d weekly regional',
                $results['daily_planner_aggregates'],
                $results['daily_regional_aggregates'],
                $results['weekly_planner_aggregates'],
                $results['weekly_regional_aggregates']
            );

            $outputLogger?->complete($summaryMessage);

            Log::info('Aggregates build completed', [
                'sync_log_id' => $syncLog->id,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            $syncLog->fail($e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            $outputLogger?->fail('Build failed: '.$e->getMessage());

            Log::error('Aggregates build failed', [
                'sync_log_id' => $syncLog->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build daily aggregates for the target date.
     *
     * @return array{planner: int, regional: int, errors: array}
     */
    protected function buildDailyAggregates(?SyncOutputLogger $outputLogger): array
    {
        $results = ['planner' => 0, 'regional' => 0, 'errors' => []];
        $date = $this->targetDate->toDateString();

        try {
            // Build planner daily aggregates
            $outputLogger?->info('Computing planner daily aggregates...');
            $plannerData = $this->computePlannerDailyData($date);

            foreach ($plannerData as $data) {
                try {
                    PlannerDailyAggregate::updateOrCreate(
                        [
                            'user_id' => $data['user_id'],
                            'aggregate_date' => $date,
                        ],
                        $data
                    );
                    $results['planner']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'type' => 'planner_daily',
                        'user_id' => $data['user_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $outputLogger?->success("Built {$results['planner']} planner daily aggregates");

            // Build regional daily aggregates
            $outputLogger?->info('Computing regional daily aggregates...');
            $regionalData = $this->computeRegionalDailyData($date);

            foreach ($regionalData as $data) {
                try {
                    RegionalDailyAggregate::updateOrCreate(
                        [
                            'region_id' => $data['region_id'],
                            'aggregate_date' => $date,
                        ],
                        $data
                    );
                    $results['regional']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'type' => 'regional_daily',
                        'region_id' => $data['region_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $outputLogger?->success("Built {$results['regional']} regional daily aggregates");

        } catch (\Exception $e) {
            $results['errors'][] = [
                'type' => 'daily_build',
                'error' => $e->getMessage(),
            ];
            $outputLogger?->error('Daily aggregate build error: '.$e->getMessage());
        }

        return $results;
    }

    /**
     * Build weekly aggregates for the week containing the target date.
     *
     * @return array{planner: int, regional: int, errors: array}
     */
    protected function buildWeeklyAggregates(?SyncOutputLogger $outputLogger): array
    {
        $results = ['planner' => 0, 'regional' => 0, 'errors' => []];
        $weekEnding = $this->targetDate->copy()->endOfWeek(Carbon::SUNDAY);

        try {
            // Build planner weekly aggregates
            $outputLogger?->info('Computing planner weekly aggregates...');
            $plannerData = $this->computePlannerWeeklyData($weekEnding);

            foreach ($plannerData as $data) {
                try {
                    PlannerWeeklyAggregate::updateOrCreate(
                        [
                            'user_id' => $data['user_id'],
                            'week_ending' => $weekEnding->toDateString(),
                        ],
                        $data
                    );
                    $results['planner']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'type' => 'planner_weekly',
                        'user_id' => $data['user_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $outputLogger?->success("Built {$results['planner']} planner weekly aggregates");

            // Build regional weekly aggregates
            $outputLogger?->info('Computing regional weekly aggregates...');
            $regionalData = $this->computeRegionalWeeklyData($weekEnding);

            foreach ($regionalData as $data) {
                try {
                    RegionalWeeklyAggregate::updateOrCreate(
                        [
                            'region_id' => $data['region_id'],
                            'week_ending' => $weekEnding->toDateString(),
                        ],
                        $data
                    );
                    $results['regional']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'type' => 'regional_weekly',
                        'region_id' => $data['region_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $outputLogger?->success("Built {$results['regional']} regional weekly aggregates");

        } catch (\Exception $e) {
            $results['errors'][] = [
                'type' => 'weekly_build',
                'error' => $e->getMessage(),
            ];
            $outputLogger?->error('Weekly aggregate build error: '.$e->getMessage());
        }

        return $results;
    }

    /**
     * Compute planner daily aggregate data.
     *
     * @return array<int, array>
     */
    protected function computePlannerDailyData(string $date): array
    {
        // Get all circuits with their planners and latest aggregates
        return DB::table('circuit_user')
            ->join('circuits', 'circuit_user.circuit_id', '=', 'circuits.id')
            ->join('circuit_aggregates', function ($join) use ($date) {
                $join->on('circuits.id', '=', 'circuit_aggregates.circuit_id')
                    ->where('circuit_aggregates.aggregate_date', '=', $date);
            })
            ->where('circuits.is_excluded', false)
            ->whereNull('circuits.deleted_at')
            ->select(
                'circuit_user.user_id',
                DB::raw('COUNT(DISTINCT circuits.id) as circuits_worked'),
                DB::raw('SUM(circuit_aggregates.total_units) as total_units_assessed'),
                DB::raw('SUM(circuit_aggregates.total_linear_ft) as total_linear_ft'),
                DB::raw('SUM(circuit_aggregates.total_acres) as total_acres'),
                DB::raw('SUM(circuit_aggregates.total_trees) as total_trees'),
                DB::raw('SUM(circuit_aggregates.units_approved) as units_approved'),
                DB::raw('SUM(circuit_aggregates.units_refused) as units_refused'),
                DB::raw('SUM(circuit_aggregates.units_pending) as units_pending'),
                DB::raw('SUM(circuits.miles_planned) as miles_planned')
            )
            ->groupBy('circuit_user.user_id')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'aggregate_date' => $date,
                'circuits_worked' => $row->circuits_worked,
                'total_units_assessed' => $row->total_units_assessed ?? 0,
                'total_linear_ft' => $row->total_linear_ft ?? 0,
                'total_acres' => $row->total_acres ?? 0,
                'total_trees' => $row->total_trees ?? 0,
                'units_approved' => $row->units_approved ?? 0,
                'units_refused' => $row->units_refused ?? 0,
                'units_pending' => $row->units_pending ?? 0,
                'miles_planned' => $row->miles_planned ?? 0,
            ])
            ->toArray();
    }

    /**
     * Compute regional daily aggregate data.
     *
     * @return array<int, array>
     */
    protected function computeRegionalDailyData(string $date): array
    {
        return DB::table('circuits')
            ->join('circuit_aggregates', function ($join) use ($date) {
                $join->on('circuits.id', '=', 'circuit_aggregates.circuit_id')
                    ->where('circuit_aggregates.aggregate_date', '=', $date);
            })
            ->where('circuits.is_excluded', false)
            ->whereNull('circuits.deleted_at')
            ->whereNotNull('circuits.region_id')
            ->select(
                'circuits.region_id',
                DB::raw('COUNT(DISTINCT circuits.id) as total_circuits'),
                DB::raw('COUNT(DISTINCT CASE WHEN circuits.api_status = \'ACTIV\' THEN circuits.id END) as active_circuits'),
                DB::raw('COUNT(DISTINCT CASE WHEN circuits.api_status = \'QC\' THEN circuits.id END) as qc_circuits'),
                DB::raw('COUNT(DISTINCT CASE WHEN circuits.api_status = \'CLOSE\' THEN circuits.id END) as closed_circuits'),
                DB::raw('SUM(circuit_aggregates.total_units) as total_units'),
                DB::raw('SUM(circuit_aggregates.total_linear_ft) as total_linear_ft'),
                DB::raw('SUM(circuit_aggregates.total_acres) as total_acres'),
                DB::raw('SUM(circuit_aggregates.total_trees) as total_trees'),
                DB::raw('SUM(circuit_aggregates.units_approved) as units_approved'),
                DB::raw('SUM(circuit_aggregates.units_refused) as units_refused'),
                DB::raw('SUM(circuit_aggregates.units_pending) as units_pending'),
                DB::raw('SUM(circuits.total_miles) as total_miles'),
                DB::raw('SUM(circuits.miles_planned) as miles_planned'),
                DB::raw('AVG(circuits.percent_complete) as avg_percent_complete')
            )
            ->groupBy('circuits.region_id')
            ->get()
            ->map(fn ($row) => [
                'region_id' => $row->region_id,
                'aggregate_date' => $date,
                'total_circuits' => $row->total_circuits,
                'active_circuits' => $row->active_circuits ?? 0,
                'qc_circuits' => $row->qc_circuits ?? 0,
                'closed_circuits' => $row->closed_circuits ?? 0,
                'total_units' => $row->total_units ?? 0,
                'total_linear_ft' => $row->total_linear_ft ?? 0,
                'total_acres' => $row->total_acres ?? 0,
                'total_trees' => $row->total_trees ?? 0,
                'units_approved' => $row->units_approved ?? 0,
                'units_refused' => $row->units_refused ?? 0,
                'units_pending' => $row->units_pending ?? 0,
                'total_miles' => $row->total_miles ?? 0,
                'miles_planned' => $row->miles_planned ?? 0,
                'avg_percent_complete' => round($row->avg_percent_complete ?? 0, 2),
            ])
            ->toArray();
    }

    /**
     * Compute planner weekly aggregate data.
     *
     * @return array<int, array>
     */
    protected function computePlannerWeeklyData(Carbon $weekEnding): array
    {
        $weekStart = $weekEnding->copy()->startOfWeek(Carbon::MONDAY);

        // Sum daily aggregates for the week
        return PlannerDailyAggregate::query()
            ->whereBetween('aggregate_date', [$weekStart->toDateString(), $weekEnding->toDateString()])
            ->select(
                'user_id',
                DB::raw('COUNT(DISTINCT aggregate_date) as days_worked'),
                DB::raw('MAX(circuits_worked) as circuits_worked'),
                DB::raw('SUM(total_units_assessed) as total_units_assessed'),
                DB::raw('SUM(total_linear_ft) as total_linear_ft'),
                DB::raw('SUM(total_acres) as total_acres'),
                DB::raw('SUM(total_trees) as total_trees'),
                DB::raw('SUM(units_approved) as units_approved'),
                DB::raw('SUM(units_refused) as units_refused'),
                DB::raw('SUM(units_pending) as units_pending'),
                DB::raw('MAX(miles_planned) as miles_planned')
            )
            ->groupBy('user_id')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'week_ending' => $weekEnding->toDateString(),
                'week_starting' => $weekStart->toDateString(),
                'days_worked' => $row->days_worked ?? 0,
                'circuits_worked' => $row->circuits_worked ?? 0,
                'total_units_assessed' => $row->total_units_assessed ?? 0,
                'total_linear_ft' => $row->total_linear_ft ?? 0,
                'total_acres' => $row->total_acres ?? 0,
                'total_trees' => $row->total_trees ?? 0,
                'units_approved' => $row->units_approved ?? 0,
                'units_refused' => $row->units_refused ?? 0,
                'units_pending' => $row->units_pending ?? 0,
                'miles_planned' => $row->miles_planned ?? 0,
            ])
            ->toArray();
    }

    /**
     * Compute regional weekly aggregate data.
     *
     * @return array<int, array>
     */
    protected function computeRegionalWeeklyData(Carbon $weekEnding): array
    {
        $weekStart = $weekEnding->copy()->startOfWeek(Carbon::MONDAY);

        return RegionalDailyAggregate::query()
            ->whereBetween('aggregate_date', [$weekStart->toDateString(), $weekEnding->toDateString()])
            ->select(
                'region_id',
                DB::raw('MAX(total_circuits) as total_circuits'),
                DB::raw('MAX(active_circuits) as active_circuits'),
                DB::raw('MAX(qc_circuits) as qc_circuits'),
                DB::raw('MAX(closed_circuits) as closed_circuits'),
                DB::raw('SUM(total_units) as total_units'),
                DB::raw('SUM(total_linear_ft) as total_linear_ft'),
                DB::raw('SUM(total_acres) as total_acres'),
                DB::raw('SUM(total_trees) as total_trees'),
                DB::raw('SUM(units_approved) as units_approved'),
                DB::raw('SUM(units_refused) as units_refused'),
                DB::raw('SUM(units_pending) as units_pending'),
                DB::raw('MAX(total_miles) as total_miles'),
                DB::raw('MAX(miles_planned) as miles_planned'),
                DB::raw('AVG(avg_percent_complete) as avg_percent_complete')
            )
            ->groupBy('region_id')
            ->get()
            ->map(fn ($row) => [
                'region_id' => $row->region_id,
                'week_ending' => $weekEnding->toDateString(),
                'week_starting' => $weekStart->toDateString(),
                'total_circuits' => $row->total_circuits ?? 0,
                'active_circuits' => $row->active_circuits ?? 0,
                'qc_circuits' => $row->qc_circuits ?? 0,
                'closed_circuits' => $row->closed_circuits ?? 0,
                'total_units' => $row->total_units ?? 0,
                'total_linear_ft' => $row->total_linear_ft ?? 0,
                'total_acres' => $row->total_acres ?? 0,
                'total_trees' => $row->total_trees ?? 0,
                'units_approved' => $row->units_approved ?? 0,
                'units_refused' => $row->units_refused ?? 0,
                'units_pending' => $row->units_pending ?? 0,
                'total_miles' => $row->total_miles ?? 0,
                'miles_planned' => $row->miles_planned ?? 0,
                'avg_percent_complete' => round($row->avg_percent_complete ?? 0, 2),
            ])
            ->toArray();
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Aggregates build job failed permanently', [
            'aggregate_type' => $this->aggregateType,
            'target_date' => $this->targetDate?->toDateString(),
            'trigger' => $this->triggerType->value,
            'triggered_by' => $this->triggeredByUserId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'aggregates',
            "type:{$this->aggregateType}",
            "date:{$this->targetDate?->toDateString()}",
        ];
    }
}
