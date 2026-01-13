<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiStatusConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_status',
        'display_name',
        'sync_frequency',
        'sync_planned_units',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sync_planned_units' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to only active configs.
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

    /**
     * Find by API status value.
     */
    public static function findByStatus(string $status): ?self
    {
        return static::where('api_status', $status)->first();
    }

    /**
     * Get statuses that should sync daily.
     */
    public static function dailySyncStatuses(): array
    {
        return static::active()
            ->where('sync_frequency', 'daily')
            ->pluck('api_status')
            ->toArray();
    }

    /**
     * Get statuses that should sync weekly.
     */
    public static function weeklySyncStatuses(): array
    {
        return static::active()
            ->where('sync_frequency', 'weekly')
            ->pluck('api_status')
            ->toArray();
    }
}
