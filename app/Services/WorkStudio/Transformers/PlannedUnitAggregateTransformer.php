<?php

namespace App\Services\WorkStudio\Transformers;

use App\Models\UnitType;
use Illuminate\Support\Collection;

/**
 * Transform planned units from API into aggregate data.
 *
 * This transformer does NOT return individual unit records.
 * Instead, it computes totals and breakdowns for aggregate storage.
 */
class PlannedUnitAggregateTransformer
{
    /**
     * API field mappings for planned unit data.
     */
    private const FIELD_MAP = [
        'region' => 'VEGJOB_REGION',
        'work_order' => 'SS_WO',
        'extension' => 'SS_EXT',
        'unit_type' => 'VEGUNIT_UNIT',
        'permission_status' => 'VEGUNIT_PERMSTAT',
        'forester' => 'VEGUNIT_FORESTER',
        'num_trees' => 'JOBVEGETATIONUNITS_NUMTREES',
        'length_work' => 'JOBVEGETATIONUNITS_LENGTHWRK',
        'acres' => 'JOBVEGETATIONUNITS_ACRES',
        'assess_date' => 'VEGUNIT_ASSDDATE',
        'object_id' => 'SSUNITS_OBJECTID',
    ];

    /**
     * Transform raw planned units into aggregate summary.
     *
     * @param  Collection  $rawUnits  Collection of raw API unit data
     * @return array Aggregate summary data
     */
    public function transformToAggregate(Collection $rawUnits): array
    {
        if ($rawUnits->isEmpty()) {
            return $this->emptyAggregate();
        }

        // Get unit type categories for measurement grouping
        $linearFtCodes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_LINEAR_FT);
        $acresCodes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_ACRES);
        $treeCountCodes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_TREE_COUNT);

        return [
            // Totals
            'total_units' => $rawUnits->count(),
            'total_linear_ft' => $this->sumByUnitTypes($rawUnits, $linearFtCodes, 'JOBVEGETATIONUNITS_LENGTHWRK'),
            'total_acres' => $this->sumByUnitTypes($rawUnits, $acresCodes, 'JOBVEGETATIONUNITS_ACRES'),
            'total_trees' => $this->sumByUnitTypes($rawUnits, $treeCountCodes, 'JOBVEGETATIONUNITS_NUMTREES'),

            // Permission breakdowns
            'units_approved' => $rawUnits->where('VEGUNIT_PERMSTAT', 'Approved')->count(),
            'units_refused' => $rawUnits->where('VEGUNIT_PERMSTAT', 'Refused')->count(),
            'units_pending' => $rawUnits->filter(function ($unit) {
                $status = $unit['VEGUNIT_PERMSTAT'] ?? '';

                return empty($status) || strtolower($status) === 'pending';
            })->count(),

            // Breakdowns by type (JSONB storage)
            'unit_counts_by_type' => $this->countByUnitType($rawUnits),
            'linear_ft_by_type' => $this->sumByUnitType($rawUnits, 'JOBVEGETATIONUNITS_LENGTHWRK', $linearFtCodes),
            'acres_by_type' => $this->sumByUnitType($rawUnits, 'JOBVEGETATIONUNITS_ACRES', $acresCodes),

            // Planner distribution (JSONB storage)
            'planner_distribution' => $this->buildPlannerDistribution($rawUnits),

            // Permission status breakdown (JSONB storage)
            'permission_counts' => $this->countByPermissionStatus($rawUnits),
        ];
    }

    /**
     * Transform and group by planner for planner-level aggregates.
     *
     * @return Collection<string, array> Keyed by planner name
     */
    public function transformByPlanner(Collection $rawUnits): Collection
    {
        return $rawUnits
            ->groupBy(fn ($unit) => $unit['VEGUNIT_FORESTER'] ?? 'Unknown')
            ->map(fn ($plannerUnits) => $this->transformToAggregate(collect($plannerUnits)));
    }

    /**
     * Get an empty aggregate structure.
     */
    public function emptyAggregate(): array
    {
        return [
            'total_units' => 0,
            'total_linear_ft' => 0,
            'total_acres' => 0,
            'total_trees' => 0,
            'units_approved' => 0,
            'units_refused' => 0,
            'units_pending' => 0,
            'unit_counts_by_type' => [],
            'linear_ft_by_type' => [],
            'acres_by_type' => [],
            'planner_distribution' => [],
            'permission_counts' => [],
        ];
    }

    /**
     * Sum a field for units matching specific unit type codes.
     */
    protected function sumByUnitTypes(Collection $units, array $codes, string $field): float
    {
        return (float) $units
            ->whereIn('VEGUNIT_UNIT', $codes)
            ->sum($field);
    }

    /**
     * Count units grouped by unit type code.
     */
    protected function countByUnitType(Collection $units): array
    {
        return $units
            ->groupBy('VEGUNIT_UNIT')
            ->map->count()
            ->filter(fn ($count) => $count > 0)
            ->toArray();
    }

    /**
     * Sum a field grouped by unit type code.
     */
    protected function sumByUnitType(Collection $units, string $field, array $validCodes): array
    {
        return $units
            ->whereIn('VEGUNIT_UNIT', $validCodes)
            ->groupBy('VEGUNIT_UNIT')
            ->map(fn ($group) => round($group->sum($field), 4))
            ->filter(fn ($sum) => $sum > 0)
            ->toArray();
    }

    /**
     * Count units grouped by permission status.
     */
    protected function countByPermissionStatus(Collection $units): array
    {
        return $units
            ->groupBy(fn ($unit) => $unit['VEGUNIT_PERMSTAT'] ?? 'Unknown')
            ->map->count()
            ->toArray();
    }

    /**
     * Build planner distribution showing units per planner.
     */
    protected function buildPlannerDistribution(Collection $units): array
    {
        return $units
            ->groupBy(fn ($unit) => $unit['VEGUNIT_FORESTER'] ?? 'Unknown')
            ->map(function ($plannerUnits) {
                return [
                    'unit_count' => $plannerUnits->count(),
                    'linear_ft' => round($plannerUnits->sum('JOBVEGETATIONUNITS_LENGTHWRK'), 2),
                    'acres' => round($plannerUnits->sum('JOBVEGETATIONUNITS_ACRES'), 4),
                    'trees' => (int) $plannerUnits->sum('JOBVEGETATIONUNITS_NUMTREES'),
                ];
            })
            ->toArray();
    }

    /**
     * Extract unique planner names from units.
     */
    public function extractPlannerNames(Collection $units): array
    {
        return $units
            ->pluck('VEGUNIT_FORESTER')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get the work order from a collection of units.
     */
    public function getWorkOrder(Collection $units): ?string
    {
        return $units->first()['SS_WO'] ?? null;
    }

    /**
     * Get the extension from a collection of units.
     */
    public function getExtension(Collection $units): string
    {
        return $units->first()['SS_EXT'] ?? '@';
    }
}
