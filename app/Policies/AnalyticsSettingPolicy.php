<?php

namespace App\Policies;

use App\Models\AnalyticsSetting;
use App\Models\User;

class AnalyticsSettingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'sudo_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AnalyticsSetting $analyticsSetting): bool
    {
        return $user->hasRole(['admin', 'sudo_admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // Singleton - never create via UI
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?AnalyticsSetting $analyticsSetting = null): bool
    {
        return $user->hasRole('sudo_admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AnalyticsSetting $analyticsSetting): bool
    {
        return false; // Singleton - never delete
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AnalyticsSetting $analyticsSetting): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AnalyticsSetting $analyticsSetting): bool
    {
        return false;
    }
}
