<?php

namespace App\Models;

use App\Enums\WorkflowStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Circuit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_guid',
        'work_order',
        'extension',
        'parent_circuit_id',
        'region_id',
        'title',
        'contractor',
        'cycle_type',
        'total_miles',
        'miles_planned',
        'percent_complete',
        'total_acres',
        'start_date',
        'api_modified_date',
        'api_status',
        'api_data_json',
        'last_synced_at',
        'last_planned_units_synced_at',
        'planned_units_sync_enabled',
    ];

    protected function casts(): array
    {
        return [
            'total_miles' => 'decimal:2',
            'miles_planned' => 'decimal:2',
            'percent_complete' => 'decimal:2',
            'total_acres' => 'decimal:2',
            'start_date' => 'date',
            'api_modified_date' => 'date',
            'api_data_json' => 'array',
            'last_synced_at' => 'datetime',
            'last_planned_units_synced_at' => 'datetime',
            'planned_units_sync_enabled' => 'boolean',
        ];
    }

    /**
     * The region this circuit belongs to.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Parent circuit (for split assessments).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Circuit::class, 'parent_circuit_id');
    }

    /**
     * Child circuits (splits from this assessment).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Circuit::class, 'parent_circuit_id');
    }

    /**
     * The UI state for this circuit.
     */
    public function uiState(): HasOne
    {
        return $this->hasOne(CircuitUiState::class);
    }

    /**
     * Planners assigned to this circuit.
     */
    public function planners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'circuit_user')
            ->withPivot(['assignment_source', 'assigned_at', 'assigned_by', 'ws_user_guid'])
            ->withTimestamps();
    }

    /**
     * Snapshots of this circuit.
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(CircuitSnapshot::class);
    }

    /**
     * Aggregates for this circuit.
     */
    public function aggregates(): HasMany
    {
        return $this->hasMany(CircuitAggregate::class);
    }

    /**
     * Get the latest aggregate for this circuit.
     */
    public function latestAggregate(): HasOne
    {
        return $this->hasOne(CircuitAggregate::class)
            ->latestOfMany('aggregate_date');
    }

    /**
     * Get or create the UI state for this circuit.
     */
    public function getOrCreateUiState(): CircuitUiState
    {
        return $this->uiState ?? $this->uiState()->create([
            'workflow_stage' => WorkflowStage::Active->value,
            'stage_changed_at' => now(),
        ]);
    }

    /**
     * Get the current workflow stage.
     */
    public function getWorkflowStage(): WorkflowStage
    {
        return WorkflowStage::from($this->uiState?->workflow_stage ?? 'active');
    }

    /**
     * Check if this is a split assessment.
     */
    public function isSplit(): bool
    {
        return $this->parent_circuit_id !== null;
    }

    /**
     * Check if this circuit has splits.
     */
    public function hasSplits(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get display work order (with extension).
     */
    public function getDisplayWorkOrderAttribute(): string
    {
        $ext = $this->extension === '@' ? '' : $this->extension;

        return $this->work_order.($ext ? " ({$ext})" : '');
    }

    /**
     * Scope by API status.
     */
    public function scopeByApiStatus($query, string $status)
    {
        return $query->where('api_status', $status);
    }

    /**
     * Scope to active circuits (not soft-deleted).
     */
    public function scopeActive($query)
    {
        return $query->where('api_status', 'ACTIV');
    }

    /**
     * Scope by region.
     */
    public function scopeInRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Scope to circuits needing sync.
     */
    public function scopeNeedsSync($query)
    {
        return $query->where('planned_units_sync_enabled', true)
            ->where(function ($q) {
                $q->whereNull('last_planned_units_synced_at')
                    ->orWhere('last_planned_units_synced_at', '<', now()->subHours(12));
            });
    }
}
