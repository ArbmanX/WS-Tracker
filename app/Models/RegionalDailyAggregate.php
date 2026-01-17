<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalDailyAggregate extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'aggregate_date',
        'active_circuits',
        'qc_circuits',
        'closed_circuits',
        'total_circuits',
        'total_miles',
        'miles_planned',
        'avg_percent_complete',
        'total_units',
        'total_linear_ft',
        'total_acres',
        'total_trees',
        'units_approved',
        'units_refused',
        'units_pending',
        'active_planners',
        'total_planners',
        'unit_counts_by_type',
        'status_breakdown',
        'permission_counts',
    ];

    protected function casts(): array
    {
        return [
            'aggregate_date' => 'date',
            'total_miles' => 'decimal:2',
            'miles_planned' => 'decimal:2',
            'avg_percent_complete' => 'decimal:2',
            'total_linear_ft' => 'decimal:2',
            'total_acres' => 'decimal:4',
            'unit_counts_by_type' => 'array',
            'status_breakdown' => 'array',
            'permission_counts' => 'array',
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
     * Scope by date range.
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('aggregate_date', [$start, $end]);
    }

    /**
     * Scope for a specific region.
     */
    public function scopeForRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Scope to a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('aggregate_date', $date);
    }

    /**
     * Scope to latest per region.
     */
    public function scopeLatestPerRegion($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('regional_daily_aggregates')
                ->groupBy('region_id');
        });
    }
}
