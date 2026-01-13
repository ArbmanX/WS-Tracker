<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Circuits in this region.
     */
    public function circuits(): HasMany
    {
        return $this->hasMany(Circuit::class);
    }

    /**
     * Users assigned to this region.
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_regions')
            ->withTimestamps();
    }

    /**
     * Daily aggregates for this region.
     */
    public function dailyAggregates(): HasMany
    {
        return $this->hasMany(RegionalDailyAggregate::class);
    }

    /**
     * Scope to only active regions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
