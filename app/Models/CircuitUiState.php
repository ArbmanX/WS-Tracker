<?php

namespace App\Models;

use App\Enums\WorkflowStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircuitUiState extends Model
{
    use HasFactory;

    protected $fillable = [
        'circuit_id',
        'workflow_stage',
        'stage_position',
        'stage_changed_at',
        'stage_changed_by',
        'is_hidden',
        'is_pinned',
        'custom_color',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'workflow_stage' => WorkflowStage::class,
            'stage_changed_at' => 'datetime',
            'is_hidden' => 'boolean',
            'is_pinned' => 'boolean',
        ];
    }

    /**
     * The circuit this UI state belongs to.
     */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class);
    }

    /**
     * The user who last changed the stage.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stage_changed_by');
    }

    /**
     * Move to a new workflow stage.
     */
    public function moveToStage(WorkflowStage $stage, ?User $user = null, ?int $position = null): void
    {
        $this->update([
            'workflow_stage' => $stage,
            'stage_position' => $position ?? $this->getNextPositionForStage($stage),
            'stage_changed_at' => now(),
            'stage_changed_by' => $user?->id,
        ]);
    }

    /**
     * Get the next available position for a stage.
     */
    protected function getNextPositionForStage(WorkflowStage $stage): int
    {
        return static::where('workflow_stage', $stage)->max('stage_position') + 1;
    }

    /**
     * Scope by workflow stage.
     */
    public function scopeInStage($query, WorkflowStage $stage)
    {
        return $query->where('workflow_stage', $stage);
    }

    /**
     * Scope to visible circuits (not hidden).
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope to pinned circuits.
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope ordered by position within stage.
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_pinned')
            ->orderBy('stage_position');
    }
}
