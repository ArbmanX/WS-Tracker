<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ws_user_guid',
        'ws_username',
        'is_ws_linked',
        'ws_linked_at',
        'default_region_id',
        'theme_preference',
        'onboarded_at',
        'dashboard_preferences',
        'is_excluded_from_analytics',
        'exclusion_reason',
        'excluded_by',
        'excluded_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_ws_linked' => 'boolean',
            'ws_linked_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'dashboard_preferences' => 'array',
            'is_excluded_from_analytics' => 'boolean',
            'excluded_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * The user's default region.
     */
    public function defaultRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'default_region_id');
    }

    /**
     * Regions assigned to this user.
     */
    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'user_regions')
            ->withTimestamps();
    }

    /**
     * WorkStudio credentials for this user.
     */
    public function wsCredentials(): HasOne
    {
        return $this->hasOne(UserWsCredential::class);
    }

    /**
     * Circuits assigned to this user (as planner).
     */
    public function circuits(): BelongsToMany
    {
        return $this->belongsToMany(Circuit::class, 'circuit_user')
            ->withPivot(['assignment_source', 'assigned_at', 'assigned_by', 'ws_user_guid'])
            ->withTimestamps();
    }

    /**
     * Daily productivity aggregates for this user.
     */
    public function dailyAggregates(): HasMany
    {
        return $this->hasMany(PlannerDailyAggregate::class);
    }

    /**
     * Sync logs triggered by this user.
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'triggered_by');
    }

    /**
     * Check if user is linked to WorkStudio.
     */
    public function isWsLinked(): bool
    {
        return $this->is_ws_linked && $this->ws_user_guid !== null;
    }

    /**
     * Get the contractor prefix from ws_username (e.g., "ASPLUNDH" from "ASPLUNDH\jsmith").
     */
    public function getContractorAttribute(): ?string
    {
        if (! $this->ws_username) {
            return null;
        }

        // Split by backslash and return first part
        $parts = explode('\\', $this->ws_username);

        return count($parts) > 1 ? $parts[0] : null;
    }

    /**
     * Get the username without contractor prefix (e.g., "jsmith" from "ASPLUNDH\jsmith").
     */
    public function getWsUsernameShortAttribute(): ?string
    {
        if (! $this->ws_username) {
            return null;
        }

        $parts = explode('\\', $this->ws_username);

        return count($parts) > 1 ? $parts[1] : $this->ws_username;
    }

    /**
     * Check if user has valid WS credentials.
     */
    public function hasValidWsCredentials(): bool
    {
        return $this->wsCredentials?->is_valid ?? false;
    }

    /**
     * Scope to users linked to WorkStudio.
     */
    public function scopeWsLinked($query)
    {
        return $query->where('is_ws_linked', true);
    }

    /**
     * Scope to planners (users with planner role).
     */
    public function scopePlanners($query)
    {
        return $query->role('planner');
    }

    /**
     * Scope to users in a specific region.
     */
    public function scopeInRegion($query, $regionId)
    {
        return $query->whereHas('regions', function ($q) use ($regionId) {
            $q->where('regions.id', $regionId);
        });
    }

    /**
     * Check if user has completed onboarding.
     */
    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null;
    }

    /**
     * Check if user is pending onboarding.
     */
    public function isPendingOnboarding(): bool
    {
        return $this->onboarded_at === null;
    }

    /**
     * Mark user as onboarded.
     */
    public function markAsOnboarded(): void
    {
        $this->update(['onboarded_at' => now()]);
    }

    /**
     * Scope to users who have completed onboarding.
     */
    public function scopeOnboarded($query)
    {
        return $query->whereNotNull('onboarded_at');
    }

    /**
     * Scope to users who are pending onboarding.
     */
    public function scopePendingOnboarding($query)
    {
        return $query->whereNull('onboarded_at');
    }

    /**
     * User who excluded this planner from analytics.
     */
    public function excludedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'excluded_by');
    }

    /**
     * Check if user is excluded from analytics.
     */
    public function isExcludedFromAnalytics(): bool
    {
        return $this->is_excluded_from_analytics;
    }

    /**
     * Exclude this user from analytics.
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
     * Include this user back in analytics.
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
     * Scope to users not excluded from analytics.
     */
    public function scopeIncludedInAnalytics($query)
    {
        return $query->where('is_excluded_from_analytics', false);
    }

    /**
     * Scope to users excluded from analytics.
     */
    public function scopeExcludedFromAnalytics($query)
    {
        return $query->where('is_excluded_from_analytics', true);
    }

    /**
     * Scope to users with a specific contractor prefix in ws_username.
     *
     * @param  array<string>|string  $contractors
     */
    public function scopeWithContractor($query, array|string $contractors)
    {
        $contractors = (array) $contractors;

        return $query->where(function ($q) use ($contractors) {
            foreach ($contractors as $contractor) {
                $q->orWhere('ws_username', 'like', $contractor.'\\%');
            }
        });
    }

    /**
     * Scope to users with any of the allowed contractors (from analytics settings).
     * If no contractors are specified in settings, includes all users.
     */
    public function scopeWithAllowedContractors($query)
    {
        $allowedContractors = AnalyticsSetting::getSelectedContractors();

        if ($allowedContractors === null || empty($allowedContractors)) {
            return $query; // No filter - include all
        }

        return $query->withContractor($allowedContractors);
    }
}
