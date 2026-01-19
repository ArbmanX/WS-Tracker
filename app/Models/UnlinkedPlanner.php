<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnlinkedPlanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'ws_user_guid',
        'ws_username',
        'display_name',
        'circuit_count',
        'first_seen_at',
        'last_seen_at',
        'linked_to_user_id',
        'linked_at',
        'is_excluded_from_analytics',
        'exclusion_reason',
        'excluded_by',
        'excluded_at',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'linked_at' => 'datetime',
            'is_excluded_from_analytics' => 'boolean',
            'excluded_at' => 'datetime',
        ];
    }

    /**
     * The user this planner was linked to (if any).
     */
    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_to_user_id');
    }

    /**
     * Scope to only unlinked planners.
     */
    public function scopeUnlinked($query)
    {
        return $query->whereNull('linked_to_user_id');
    }

    /**
     * Scope to only linked planners.
     */
    public function scopeLinked($query)
    {
        return $query->whereNotNull('linked_to_user_id');
    }

    /**
     * Check if this planner is linked.
     */
    public function isLinked(): bool
    {
        return $this->linked_to_user_id !== null;
    }

    /**
     * Link this planner to a user.
     */
    public function linkToUser(User $user): void
    {
        $this->update([
            'linked_to_user_id' => $user->id,
            'linked_at' => now(),
        ]);

        // Also update the user's WS link
        $user->update([
            'ws_user_guid' => $this->ws_user_guid,
            'ws_username' => $this->ws_username,
            'is_ws_linked' => true,
            'ws_linked_at' => now(),
        ]);
    }

    /**
     * Update seen timestamp and circuit count.
     */
    public function recordActivity(?int $circuitCount = null): void
    {
        $data = ['last_seen_at' => now()];

        if ($circuitCount !== null) {
            $data['circuit_count'] = $circuitCount;
        }

        $this->update($data);
    }

    /**
     * User who excluded this planner from analytics.
     */
    public function excludedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'excluded_by');
    }

    /**
     * Check if planner is excluded from analytics.
     */
    public function isExcludedFromAnalytics(): bool
    {
        return $this->is_excluded_from_analytics;
    }

    /**
     * Exclude this planner from analytics.
     */
    public function excludeFromAnalytics(string $reason, ?User $excludedBy = null): void
    {
        $this->update([
            'is_excluded_from_analytics' => true,
            'exclusion_reason' => $reason,
            'excluded_by' => $excludedBy?->id,
            'excluded_at' => now(),
        ]);
    }

    /**
     * Include this planner back in analytics.
     */
    public function includeInAnalytics(): void
    {
        $this->update([
            'is_excluded_from_analytics' => false,
            'exclusion_reason' => null,
            'excluded_by' => null,
            'excluded_at' => null,
        ]);
    }

    /**
     * Scope to planners not excluded from analytics.
     */
    public function scopeIncludedInAnalytics($query)
    {
        return $query->where('is_excluded_from_analytics', false);
    }

    /**
     * Scope to planners excluded from analytics.
     */
    public function scopeExcludedFromAnalytics($query)
    {
        return $query->where('is_excluded_from_analytics', true);
    }
}
