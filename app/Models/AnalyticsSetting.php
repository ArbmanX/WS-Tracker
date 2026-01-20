<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class AnalyticsSetting extends Model
{
    protected $fillable = [
        'scope_year',
        'selected_cycle_types',
        'selected_contractors',
        'planned_units_sync_enabled',
        'sync_interval_hours',
        'aggregates_retention_days',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'selected_cycle_types' => 'array',
            'selected_contractors' => 'array',
            'planned_units_sync_enabled' => 'boolean',
            'sync_interval_hours' => 'integer',
            'aggregates_retention_days' => 'integer',
        ];
    }

    /**
     * Cache key for the singleton instance.
     */
    public const CACHE_KEY = 'analytics_settings';

    /**
     * Cache duration in seconds (1 hour).
     */
    public const CACHE_TTL = 3600;

    /**
     * Get the singleton instance (cached).
     */
    public static function instance(): self
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::query()->firstOrCreate(
                ['id' => 1],
                [
                    'scope_year' => date('Y'),
                    'selected_cycle_types' => null,
                    'selected_contractors' => null,
                ]
            );
        });
    }

    /**
     * Clear the cached instance.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Boot the model to clear cache on save.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            self::clearCache();
        });
    }

    /**
     * User who last updated these settings.
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the current scope year setting.
     */
    public static function getScopeYear(): string
    {
        return self::instance()->scope_year ?? date('Y');
    }

    /**
     * Get selected cycle types (null means all).
     *
     * @return array<string>|null
     */
    public static function getSelectedCycleTypes(): ?array
    {
        return self::instance()->selected_cycle_types;
    }

    /**
     * Get selected contractors (null means all).
     *
     * @return array<string>|null
     */
    public static function getSelectedContractors(): ?array
    {
        return self::instance()->selected_contractors;
    }

    /**
     * Check if a cycle type is included in analytics.
     */
    public static function isCycleTypeIncluded(?string $cycleType): bool
    {
        $selected = self::getSelectedCycleTypes();

        // null means all types are included
        if ($selected === null) {
            return true;
        }

        return in_array($cycleType, $selected, true);
    }

    /**
     * Check if a contractor is included in analytics.
     */
    public static function isContractorIncluded(?string $contractor): bool
    {
        $selected = self::getSelectedContractors();

        // null means all contractors are included
        if ($selected === null) {
            return true;
        }

        return in_array($contractor, $selected, true);
    }

    /**
     * Update settings with audit trail.
     *
     * @param  array<string, mixed>  $settings
     */
    public static function updateSettings(array $settings, ?User $updatedBy = null): self
    {
        $instance = self::instance();

        $instance->fill($settings);
        $instance->updated_by = $updatedBy?->id;
        $instance->save();

        return $instance;
    }

    /**
     * Get available scope years from circuits.
     * Extracts year from work_order format "YYYY-XXXX".
     *
     * @return array<string>
     */
    public static function getAvailableScopeYears(): array
    {
        // Use a database-agnostic approach by processing in PHP
        return Circuit::query()
            ->select('work_order')
            ->whereNotNull('work_order')
            ->where('work_order', 'like', '____-%') // 4 chars + dash
            ->distinct('work_order')
            ->pluck('work_order')
            ->map(fn ($wo) => substr($wo, 0, 4))
            ->filter(fn ($year) => is_numeric($year))
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();
    }

    /**
     * Get available cycle types from circuits.
     *
     * @return array<string>
     */
    public static function getAvailableCycleTypes(): array
    {
        return Circuit::query()
            ->whereNotNull('cycle_type')
            ->distinct()
            ->orderBy('cycle_type')
            ->pluck('cycle_type')
            ->toArray();
    }

    /**
     * Get available contractors from user ws_usernames.
     *
     * @return array<string>
     */
    public static function getAvailableContractors(): array
    {
        return User::query()
            ->role('planner')
            ->whereNotNull('ws_username')
            ->where('ws_username', 'like', '%\\%')
            ->get()
            ->map(fn (User $user) => $user->contractor)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Check if planned units sync is globally enabled.
     */
    public static function isPlannedUnitsSyncEnabled(): bool
    {
        return self::instance()->planned_units_sync_enabled ?? true;
    }

    /**
     * Get the configured sync interval in hours.
     */
    public static function getSyncIntervalHours(): int
    {
        return self::instance()->sync_interval_hours ?? 12;
    }

    /**
     * Get the aggregates retention period in days.
     */
    public static function getAggregatesRetentionDays(): int
    {
        return self::instance()->aggregates_retention_days ?? 90;
    }

    /**
     * Get the sync interval as a Carbon interval for queries.
     */
    public static function getSyncIntervalCarbon(): \Carbon\CarbonInterval
    {
        return \Carbon\CarbonInterval::hours(self::getSyncIntervalHours());
    }
}
