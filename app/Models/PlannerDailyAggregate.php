<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannerDailyAggregate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'region_id',
        'aggregate_date',
        'circuits_worked',
        'total_units_assessed',
        'total_linear_ft',
        'total_acres',
        'total_trees',
        'units_approved',
        'units_refused',
        'units_pending',
        'unit_counts_by_type',
        'circuit_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'aggregate_date' => 'date',
            'total_linear_ft' => 'decimal:2',
            'total_acres' => 'decimal:4',
            'unit_counts_by_type' => 'array',
            'circuit_breakdown' => 'array',
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
     * Calculate productivity score.
     */
    public function getProductivityScore(): float
    {
        // Simple scoring: weighted by volume
        $lineFtScore = $this->total_linear_ft / 100;
        $acreScore = $this->total_acres * 10;
        $treeScore = $this->total_trees;

        return round($lineFtScore + $acreScore + $treeScore, 1);
    }

    /**
     * Scope by date range.
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('aggregate_date', [$start, $end]);
    }

    /**
     * Scope for a specific planner.
     */
    public function scopeForPlanner($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific region.
     */
    public function scopeInRegion($query, $regionId)
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
}
