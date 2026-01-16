<?php

namespace App\Models;

use App\Enums\SnapshotTrigger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlannedUnitsSnapshot extends Model
{
    /** @use HasFactory<\Database\Factories\PlannedUnitsSnapshotFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'circuit_id',
        'work_order',
        'snapshot_trigger',
        'percent_complete',
        'api_status',
        'content_hash',
        'unit_count',
        'total_trees',
        'total_linear_ft',
        'total_acres',
        'raw_json',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_trigger' => SnapshotTrigger::class,
            'percent_complete' => 'decimal:2',
            'total_linear_ft' => 'decimal:2',
            'total_acres' => 'decimal:4',
            'raw_json' => 'array',
        ];
    }

    /**
     * The circuit this snapshot belongs to.
     */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class);
    }

    /**
     * The user who created this snapshot (for manual snapshots).
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if a snapshot with this content already exists for the circuit.
     */
    public static function hashExists(int $circuitId, string $contentHash): bool
    {
        return static::where('circuit_id', $circuitId)
            ->where('content_hash', $contentHash)
            ->exists();
    }

    /**
     * Get the most recent snapshot for a circuit.
     */
    public static function latestForCircuit(int $circuitId): ?static
    {
        return static::where('circuit_id', $circuitId)
            ->latest()
            ->first();
    }

    /**
     * Get units from the snapshot JSON.
     *
     * @return array<int, array>
     */
    public function getUnits(): array
    {
        return $this->raw_json['units'] ?? [];
    }

    /**
     * Get metadata from the snapshot JSON.
     */
    public function getMeta(): array
    {
        return $this->raw_json['meta'] ?? [];
    }

    /**
     * Get summary from the snapshot JSON.
     */
    public function getSummary(): array
    {
        return $this->raw_json['summary'] ?? [];
    }

    /**
     * Get units filtered by permission status.
     *
     * @return array<int, array>
     */
    public function getUnitsByPermission(string $permission): array
    {
        return array_filter(
            $this->getUnits(),
            fn (array $unit) => ($unit['permission'] ?? '') === $permission
        );
    }

    /**
     * Get units filtered by station.
     *
     * @return array<int, array>
     */
    public function getUnitsByStation(string $station): array
    {
        return array_filter(
            $this->getUnits(),
            fn (array $unit) => ($unit['station'] ?? '') === $station
        );
    }

    /**
     * Get units filtered by unit type.
     *
     * @return array<int, array>
     */
    public function getUnitsByType(string $type): array
    {
        return array_filter(
            $this->getUnits(),
            fn (array $unit) => ($unit['type'] ?? '') === $type
        );
    }

    /**
     * Scope to snapshots for a specific work order.
     */
    public function scopeForWorkOrder($query, string $workOrder)
    {
        return $query->where('work_order', $workOrder);
    }

    /**
     * Scope to snapshots for a specific circuit.
     */
    public function scopeForCircuit($query, int $circuitId)
    {
        return $query->where('circuit_id', $circuitId);
    }

    /**
     * Scope to snapshots by trigger type.
     */
    public function scopeByTrigger($query, SnapshotTrigger $trigger)
    {
        return $query->where('snapshot_trigger', $trigger);
    }

    /**
     * Scope to milestone snapshots only.
     */
    public function scopeMilestones($query)
    {
        return $query->whereIn('snapshot_trigger', [
            SnapshotTrigger::Milestone50,
            SnapshotTrigger::Milestone100,
        ]);
    }

    /**
     * Get timeline of snapshots for a circuit (ordered by date).
     */
    public static function timeline(int $circuitId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('circuit_id', $circuitId)
            ->orderBy('created_at')
            ->get();
    }
}
