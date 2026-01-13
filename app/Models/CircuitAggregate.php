<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircuitAggregate extends Model
{
    use HasFactory;

    protected $fillable = [
        'circuit_id',
        'aggregate_date',
        'is_rollup',
        'total_miles',
        'miles_planned',
        'miles_remaining',
        'total_units',
        'total_linear_ft',
        'total_acres',
        'total_trees',
        'units_approved',
        'units_refused',
        'units_pending',
        'unit_counts_by_type',
        'linear_ft_by_type',
        'acres_by_type',
        'planner_distribution',
    ];

    protected function casts(): array
    {
        return [
            'aggregate_date' => 'date',
            'is_rollup' => 'boolean',
            'total_miles' => 'decimal:2',
            'miles_planned' => 'decimal:2',
            'miles_remaining' => 'decimal:2',
            'total_linear_ft' => 'decimal:2',
            'total_acres' => 'decimal:4',
            'unit_counts_by_type' => 'array',
            'linear_ft_by_type' => 'array',
            'acres_by_type' => 'array',
            'planner_distribution' => 'array',
        ];
    }

    /**
     * The circuit this aggregate belongs to.
     */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class);
    }

    /**
     * Get units for a specific type.
     */
    public function getUnitCount(string $typeCode): int
    {
        return $this->unit_counts_by_type[$typeCode] ?? 0;
    }

    /**
     * Get linear feet for a specific type.
     */
    public function getLinearFt(string $typeCode): float
    {
        return $this->linear_ft_by_type[$typeCode] ?? 0.0;
    }

    /**
     * Get acres for a specific type.
     */
    public function getAcres(string $typeCode): float
    {
        return $this->acres_by_type[$typeCode] ?? 0.0;
    }

    /**
     * Get total permission counts.
     */
    public function getTotalPermissionUnits(): int
    {
        return $this->units_approved + $this->units_refused + $this->units_pending;
    }

    /**
     * Get approval rate as percentage.
     */
    public function getApprovalRate(): float
    {
        $total = $this->getTotalPermissionUnits();

        return $total > 0 ? round(($this->units_approved / $total) * 100, 1) : 0;
    }

    /**
     * Scope to rollup records only.
     */
    public function scopeRollups($query)
    {
        return $query->where('is_rollup', true);
    }

    /**
     * Scope to a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('aggregate_date', $date);
    }

    /**
     * Scope by date range.
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('aggregate_date', [$start, $end]);
    }

    /**
     * Scope to latest per circuit.
     */
    public function scopeLatestPerCircuit($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('circuit_aggregates')
                ->groupBy('circuit_id');
        });
    }
}
