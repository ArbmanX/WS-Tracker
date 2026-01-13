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
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'linked_at' => 'datetime',
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
}
