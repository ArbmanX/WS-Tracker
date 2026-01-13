<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannerWeeklyAggregate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'region_id',
        'week_ending',
        'week_starting',
        'days_worked',
        'circuits_worked',
        'total_units_assessed',
        'total_linear_ft',
        'total_acres',
        'total_trees',
        'miles_planned',
        'units_approved',
        'units_refused',
        'units_pending',
        'unit_counts_by_type',
        'daily_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'week_ending' => 'date',
            'week_starting' => 'date',
            'total_linear_ft' => 'decimal:2',
            'total_acres' => 'decimal:4',
            'miles_planned' => 'decimal:2',
            'unit_counts_by_type' => 'array',
            'daily_breakdown' => 'array',
        ];
    }

    /**
     * The user (planner) this aggregate belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The region (if filtered by region).
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
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        // If it's already Saturday, return it
        if ($date->isSaturday()) {
            return $date->copy()->startOfDay();
        }

        // Otherwise get next Saturday
        return $date->copy()->next(Carbon::SATURDAY)->startOfDay();
    }

    /**
     * Get the Sunday that starts the week containing the given date.
     * Work week is Sunday to Saturday.
     */
    public static function getWeekStartingForDate(Carbon|string $date): Carbon
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        // If it's Sunday, return it
        if ($date->isSunday()) {
            return $date->copy()->startOfDay();
        }

        // Otherwise get previous Sunday
        return $date->copy()->previous(Carbon::SUNDAY)->startOfDay();
    }

    /**
     * Calculate average daily productivity.
     */
    public function getAvgDailyUnits(): float
    {
        if ($this->days_worked <= 0) {
            return 0;
        }

        return round($this->total_units_assessed / $this->days_worked, 1);
    }

    /**
     * Scope for a specific planner.
     */
    public function scopeForPlanner($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific region.
     */
    public function scopeInRegion($query, int $regionId)
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
     * Scope to latest week per planner.
     */
    public function scopeLatestPerPlanner($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('planner_weekly_aggregates')
                ->groupBy('user_id');
        });
    }
}
