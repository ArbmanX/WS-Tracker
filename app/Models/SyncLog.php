<?php

namespace App\Models;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_type',
        'sync_status',
        'sync_trigger',
        'region_id',
        'api_status_filter',
        'started_at',
        'completed_at',
        'duration_seconds',
        'circuits_processed',
        'circuits_created',
        'circuits_updated',
        'aggregates_created',
        'error_message',
        'error_details',
        'triggered_by',
        'context_json',
    ];

    protected function casts(): array
    {
        return [
            'sync_type' => SyncType::class,
            'sync_status' => SyncStatus::class,
            'sync_trigger' => SyncTrigger::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'error_details' => 'array',
            'context_json' => 'array',
        ];
    }

    /**
     * The region this sync was scoped to (if any).
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * The user who triggered this sync (if manual).
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Start a new sync log.
     */
    public static function start(
        SyncType $type,
        SyncTrigger $trigger,
        ?int $regionId = null,
        ?string $apiStatusFilter = null,
        ?int $triggeredBy = null,
        ?array $context = null
    ): static {
        return static::create([
            'sync_type' => $type,
            'sync_status' => SyncStatus::Started,
            'sync_trigger' => $trigger,
            'region_id' => $regionId,
            'api_status_filter' => $apiStatusFilter,
            'triggered_by' => $triggeredBy,
            'started_at' => now(),
            'context_json' => $context,
        ]);
    }

    /**
     * Mark the sync as completed.
     */
    public function complete(array $results = []): void
    {
        $this->update([
            'sync_status' => SyncStatus::Completed,
            'completed_at' => now(),
            'duration_seconds' => $this->calculateDurationSeconds(),
            'circuits_processed' => $results['circuits_processed'] ?? 0,
            'circuits_created' => $results['circuits_created'] ?? 0,
            'circuits_updated' => $results['circuits_updated'] ?? 0,
            'aggregates_created' => $results['aggregates_created'] ?? 0,
        ]);
    }

    /**
     * Mark the sync as failed.
     */
    public function fail(string $message, ?array $details = null): void
    {
        $this->update([
            'sync_status' => SyncStatus::Failed,
            'completed_at' => now(),
            'duration_seconds' => $this->calculateDurationSeconds(),
            'error_message' => $message,
            'error_details' => $details,
        ]);
    }

    /**
     * Mark the sync as completed with warnings.
     */
    public function completeWithWarning(string $message, array $results = []): void
    {
        $this->update([
            'sync_status' => SyncStatus::Warning,
            'completed_at' => now(),
            'duration_seconds' => $this->calculateDurationSeconds(),
            'error_message' => $message,
            'circuits_processed' => $results['circuits_processed'] ?? 0,
            'circuits_created' => $results['circuits_created'] ?? 0,
            'circuits_updated' => $results['circuits_updated'] ?? 0,
            'aggregates_created' => $results['aggregates_created'] ?? 0,
        ]);
    }

    /**
     * Calculate duration in seconds since sync started.
     * Returns integer for database compatibility.
     */
    protected function calculateDurationSeconds(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        return (int) abs($this->started_at->diffInSeconds(now()));
    }

    /**
     * Scope by sync type.
     */
    public function scopeOfType($query, SyncType $type)
    {
        return $query->where('sync_type', $type);
    }

    /**
     * Scope by sync status.
     */
    public function scopeWithStatus($query, SyncStatus $status)
    {
        return $query->where('sync_status', $status);
    }

    /**
     * Scope to recent syncs.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to failed syncs.
     */
    public function scopeFailed($query)
    {
        return $query->where('sync_status', SyncStatus::Failed);
    }
}
