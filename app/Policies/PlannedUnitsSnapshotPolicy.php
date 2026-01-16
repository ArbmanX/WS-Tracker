<?php

namespace App\Policies;

use App\Models\PlannedUnitsSnapshot;
use App\Models\User;

class PlannedUnitsSnapshotPolicy
{
    /**
     * Determine whether the user can view any models.
     * All authenticated users can view snapshot lists.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * All authenticated users can view individual snapshots.
     */
    public function view(User $user, PlannedUnitsSnapshot $snapshot): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Only admins can manually trigger snapshots.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['sudo_admin', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     * Snapshots are immutable - no updates allowed.
     */
    public function update(User $user, PlannedUnitsSnapshot $snapshot): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Only sudo_admin can soft delete snapshots.
     */
    public function delete(User $user, PlannedUnitsSnapshot $snapshot): bool
    {
        return $user->hasRole('sudo_admin');
    }

    /**
     * Determine whether the user can restore the model.
     * Only sudo_admin can restore soft-deleted snapshots.
     */
    public function restore(User $user, PlannedUnitsSnapshot $snapshot): bool
    {
        return $user->hasRole('sudo_admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only sudo_admin can permanently delete (and this should rarely be used).
     */
    public function forceDelete(User $user, PlannedUnitsSnapshot $snapshot): bool
    {
        return $user->hasRole('sudo_admin');
    }
}
