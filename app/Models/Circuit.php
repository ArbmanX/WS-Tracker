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
        'user_modified_fields',
        'last_user_modified_at',
        'last_user_modified_by',
        'last_synced_at',
        'last_planned_units_synced_at',
        'planned_units_sync_enabled',
        'is_excluded',
        'exclusion_reason',
        'excluded_by',
        'excluded_at',
    ];

    /**
     * Fields that can be synced from the API but may be overridden by users.
     * These fields are tracked in user_modified_fields when changed locally.
     */
    public const SYNCABLE_FIELDS = [
        'title',
        'region_id',
        'contractor',
        'cycle_type',
        'total_miles',
        'miles_planned',
        'percent_complete',
        'total_acres',
        'start_date',
    ];

    /**
     * Fields that are always synced from the API (never user-modified).
     */
    public const API_ONLY_FIELDS = [
        'job_guid',
        'work_order',
        'extension',
        'api_status',
        'api_modified_date',
        'api_data_json',
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
            'user_modified_fields' => 'array',
            'last_user_modified_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'last_planned_units_synced_at' => 'datetime',
            'planned_units_sync_enabled' => 'boolean',
            'is_excluded' => 'boolean',
            'excluded_at' => 'datetime',
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
     * Planned units snapshots for this circuit.
     */
    public function plannedUnitsSnapshots(): HasMany
    {
        return $this->hasMany(PlannedUnitsSnapshot::class);
    }

    /**
     * Get the latest planned units snapshot for this circuit.
     */
    public function latestPlannedUnitsSnapshot(): HasOne
    {
        return $this->hasOne(PlannedUnitsSnapshot::class)
            ->latestOfMany();
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
        $stage = $this->uiState->workflow_stage ?? 'active';

        if ($stage instanceof WorkflowStage) {
            return $stage;
        }

        return WorkflowStage::from($stage);
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

    /**
     * The user who excluded this circuit.
     */
    public function excludedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'excluded_by');
    }

    /**
     * Get miles remaining (total - planned).
     */
    public function getMilesRemainingAttribute(): float
    {
        return round((float) $this->total_miles - (float) $this->miles_planned, 2);
    }

    /**
     * Scope to excluded circuits.
     */
    public function scopeExcluded($query)
    {
        return $query->where('is_excluded', true);
    }

    /**
     * Scope to non-excluded circuits (for reporting).
     */
    public function scopeNotExcluded($query)
    {
        return $query->where('is_excluded', false);
    }

    /**
     * Scope to circuits included in reporting (alias for notExcluded).
     */
    public function scopeForReporting($query)
    {
        return $query->notExcluded();
    }

    /**
     * Mark this circuit as excluded from reporting.
     */
    public function exclude(?string $reason = null, ?int $excludedByUserId = null): void
    {
        $this->update([
            'is_excluded' => true,
            'exclusion_reason' => $reason,
            'excluded_by' => $excludedByUserId,
            'excluded_at' => now(),
        ]);
    }

    /**
     * Mark this circuit as included in reporting.
     */
    public function include(): void
    {
        $this->update([
            'is_excluded' => false,
            'exclusion_reason' => null,
            'excluded_by' => null,
            'excluded_at' => null,
        ]);
    }

    /**
     * Check if a field has been modified by a user.
     */
    public function isFieldUserModified(string $field): bool
    {
        $modifiedFields = $this->user_modified_fields ?? [];

        return array_key_exists($field, $modifiedFields);
    }

    /**
     * Get all user-modified field names.
     *
     * @return array<string>
     */
    public function getUserModifiedFieldNames(): array
    {
        return array_keys($this->user_modified_fields ?? []);
    }

    /**
     * Mark a field as user-modified.
     */
    public function markFieldAsUserModified(string $field, ?int $userId = null): void
    {
        if (! in_array($field, self::SYNCABLE_FIELDS)) {
            return; // Only track syncable fields
        }

        $modifiedFields = $this->user_modified_fields ?? [];
        $modifiedFields[$field] = [
            'modified_at' => now()->toIso8601String(),
            'modified_by' => $userId,
            'original_value' => $this->getOriginal($field),
        ];

        $this->user_modified_fields = $modifiedFields;
        $this->last_user_modified_at = now();
        $this->last_user_modified_by = $userId;
    }

    /**
     * Mark multiple fields as user-modified.
     *
     * @param  array<string>  $fields
     */
    public function markFieldsAsUserModified(array $fields, ?int $userId = null): void
    {
        foreach ($fields as $field) {
            $this->markFieldAsUserModified($field, $userId);
        }
    }

    /**
     * Clear user modification tracking for a field.
     */
    public function clearFieldUserModification(string $field): void
    {
        $modifiedFields = $this->user_modified_fields ?? [];
        unset($modifiedFields[$field]);

        $this->user_modified_fields = empty($modifiedFields) ? null : $modifiedFields;
    }

    /**
     * Clear all user modification tracking (used during force-sync).
     */
    public function clearAllUserModifications(): void
    {
        $this->user_modified_fields = null;
        $this->last_user_modified_at = null;
        $this->last_user_modified_by = null;
    }

    /**
     * Check if this circuit has any user modifications.
     */
    public function hasUserModifications(): bool
    {
        return ! empty($this->user_modified_fields);
    }

    /**
     * Scope to circuits with user modifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Circuit>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Circuit>
     */
    public function scopeWithUserModifications($query)
    {
        $driver = $query->getConnection()->getDriverName();

        return $query->whereNotNull('user_modified_fields')
            ->when($driver === 'pgsql', function ($q) {
                $q->whereRaw("jsonb_array_length(COALESCE(user_modified_fields, '{}')::jsonb) > 0 OR jsonb_typeof(user_modified_fields) = 'object' AND user_modified_fields != '{}'::jsonb");
            }, function ($q) {
                // SQLite/MySQL: Check string representation is not empty object
                $q->where('user_modified_fields', '!=', '{}')
                    ->where('user_modified_fields', '!=', '[]');
            });
    }

    /**
     * Scope to circuits without user modifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Circuit>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Circuit>
     */
    public function scopeWithoutUserModifications($query)
    {
        $driver = $query->getConnection()->getDriverName();

        return $query->where(function ($q) use ($driver) {
            $q->whereNull('user_modified_fields');

            if ($driver === 'pgsql') {
                $q->orWhereRaw("user_modified_fields = '{}'::jsonb");
            } else {
                // SQLite/MySQL: Check string representation
                $q->orWhere('user_modified_fields', '{}')
                    ->orWhere('user_modified_fields', '[]');
            }
        });
    }

    /**
     * The user who last modified this circuit.
     */
    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_user_modified_by');
    }
}
