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
}
