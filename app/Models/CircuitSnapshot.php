<?php

namespace App\Models;

use App\Enums\SnapshotType;
use App\Enums\WorkflowStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircuitSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'circuit_id',
        'snapshot_type',
        'snapshot_date',
        'miles_planned',
        'percent_complete',
        'api_status',
        'workflow_stage',
        'total_units',
        'total_linear_ft',
        'total_acres',
        'total_trees',
        'units_approved',
        'units_refused',
        'units_pending',
        'metrics_json',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_type' => SnapshotType::class,
            'workflow_stage' => WorkflowStage::class,
            'snapshot_date' => 'date',
            'miles_planned' => 'decimal:2',
            'percent_complete' => 'decimal:2',
            'total_linear_ft' => 'decimal:2',
            'total_acres' => 'decimal:4',
            'metrics_json' => 'array',
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
     * The user who created this snapshot (if manual).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Create a snapshot from the current circuit state.
     */
    public static function createFromCircuit(
        Circuit $circuit,
        SnapshotType $type,
        ?User $user = null,
        ?string $notes = null
    ): static {
        $aggregate = $circuit->latestAggregate;

        return static::create([
            'circuit_id' => $circuit->id,
            'snapshot_type' => $type,
            'snapshot_date' => now()->toDateString(),
            'miles_planned' => $circuit->miles_planned,
            'percent_complete' => $circuit->percent_complete,
            'api_status' => $circuit->api_status,
            'workflow_stage' => $circuit->getWorkflowStage(),
            'total_units' => $aggregate?->total_units ?? 0,
            'total_linear_ft' => $aggregate?->total_linear_ft ?? 0,
            'total_acres' => $aggregate?->total_acres ?? 0,
            'total_trees' => $aggregate?->total_trees ?? 0,
            'units_approved' => $aggregate?->units_approved ?? 0,
            'units_refused' => $aggregate?->units_refused ?? 0,
            'units_pending' => $aggregate?->units_pending ?? 0,
            'metrics_json' => $aggregate ? [
                'unit_counts_by_type' => $aggregate->unit_counts_by_type,
                'planner_distribution' => $aggregate->planner_distribution,
            ] : null,
            'created_by' => $user?->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Scope by snapshot type.
     */
    public function scopeOfType($query, SnapshotType $type)
    {
        return $query->where('snapshot_type', $type);
    }

    /**
     * Scope by date range.
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('snapshot_date', [$start, $end]);
    }

    /**
     * Scope to daily snapshots.
     */
    public function scopeDaily($query)
    {
        return $query->where('snapshot_type', SnapshotType::Daily);
    }
}
