<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalWeeklyAggregate extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'week_ending',
        'week_starting',
        'active_circuits',
        'qc_circuits',
        'closed_circuits',
        'total_circuits',
        'excluded_circuits',
        'total_miles',
        'miles_planned',
        'miles_remaining',
        'avg_percent_complete',
        'total_units',
        'total_linear_ft',
        'total_acres',
        'total_trees',
        'units_approved',
        'units_refused',
        'units_pending',
        'active_planners',
        'total_planner_days',
        'unit_counts_by_type',
        'status_breakdown',
        'daily_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'week_ending' => 'date',
            'week_starting' => 'date',
            'total_miles' => 'decimal:2',
            'miles_planned' => 'decimal:2',
            'miles_remaining' => 'decimal:2',
            'avg_percent_complete' => 'decimal:2',
            'total_linear_ft' => 'decimal:2',
            'total_acres' => 'decimal:4',
            'unit_counts_by_type' => 'array',
            'status_breakdown' => 'array',
            'daily_breakdown' => 'array',
        ];
    }

    /**
     * The region this aggregate belongs to.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the Saturday that ends the week containing the given date.
     * Work week is Sunday to Saturday.
     */
    public static function getWeekEndingForDate(Carbon|string $date): Carbon
    {
        return PlannerWeeklyAggregate::getWeekEndingForDate($date);
    }

    /**
     * Get the Sunday that starts the week containing the given date.
     * Work week is Sunday to Saturday.
     */
    public static function getWeekStartingForDate(Carbon|string $date): Carbon
    {
        return PlannerWeeklyAggregate::getWeekStartingForDate($date);
    }

    /**
     * Get overall completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        if ($this->total_miles <= 0) {
            return 0;
        }

        return round(($this->miles_planned / $this->total_miles) * 100, 1);
    }

    /**
     * Get average daily production for the week.
     */
    public function getAvgDailyUnits(): float
    {
        // Assume 7 potential work days in a week
        $workDays = 7;

        return round($this->total_units / $workDays, 1);
    }

    /**
     * Scope for a specific region.
     */
    public function scopeForRegion($query, int $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Scope for a specific week ending date.
     */
    public function scopeForWeekEnding($query, $date)
    {
        return $query->whereDate('week_ending', $date);
    }

    /**
     * Scope for weeks within a date range.
     */
    public function scopeBetweenWeeks($query, $startWeekEnding, $endWeekEnding)
    {
        return $query->whereDate('week_ending', '>=', $startWeekEnding)
            ->whereDate('week_ending', '<=', $endWeekEnding);
    }

    /**
     * Scope to latest week per region.
     */
    public function scopeLatestPerRegion($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('regional_weekly_aggregates')
                ->groupBy('region_id');
        });
    }
}
