<?php

namespace App\Services\WorkStudio\Transformers;

use Illuminate\Support\Collection;

/**
 * Normalizes raw planned units API response into an optimized JSON structure
 * for snapshot storage.
 *
 * Removes redundant circuit-level data, excludes geometry, and provides
 * pre-computed summaries for efficient querying.
 */
class PlannedUnitsNormalizer
{
    /**
     * API field to normalized field mapping for units.
     */
    private const UNIT_FIELD_MAP = [
        'SSUNITS_OBJECTID' => 'id',
        'STATIONS_STATNAME' => 'station',
        'VEGUNIT_UNIT' => 'type',
        'UNITS_DESCRIPTIO' => 'desc',
        'VEGUNIT_REMOVCAT' => 'removal_cat',
        'VEGUNIT_PERMSTAT' => 'permission',
        'JOBVEGETATIONUNITS_NUMTREES' => 'trees',
        'JOBVEGETATIONUNITS_LENGTHWRK' => 'linear_ft',
        'JOBVEGETATIONUNITS_ACRES' => 'acres',
        'VEGUNIT_ASSDDATE' => 'assessed',
        'VEGUNIT_ASSLAT' => 'lat',
        'VEGUNIT_ASSLONG' => 'lng',
        'VEGUNIT_SPECIES' => 'species',
        'VEGUNIT_ASSNOTE' => 'notes',
        'VEGUNIT_PARCELCOMMENTS' => 'parcel_comments',
        'VEGUNIT_UNIT_CLASS' => 'unit_class',
        'VEGUNIT_AUDIT_PASS' => 'audit_pass',
        'VEGUNIT_AUDITOR' => 'auditor',
        'VEGUNIT_AUDITDATE' => 'audit_date',
        'VEGSTAT_FROMSTR' => 'from_structure',
        'VEGSTAT_TOSTR' => 'to_structure',
    ];

    /**
     * Fields extracted once for metadata (not repeated per unit).
     */
    private const META_FIELDS = [
        'VEGJOB_REGION' => 'region',
        'VEGJOB_CYCLETYPE' => 'cycle_type',
        'VEGSTAT_LINENAME' => 'line_name',
        'LineIDLookup_FeederID' => 'feeder_id',
        'VEGUNIT_FORESTER' => 'forester',
        'VEGJOB_CONTRACTOR' => 'contractor',
        'SS_WO' => 'work_order',
        'SS_EXT' => 'extension',
    ];

    /**
     * Normalize raw API response into optimized structure.
     *
     * @param  Collection  $rawData  Collection of raw API rows
     * @return array{meta: array, summary: array, units: array}
     */
    public function normalize(Collection $rawData): array
    {
        if ($rawData->isEmpty()) {
            return $this->emptyResponse();
        }

        // Extract metadata from first row (same for all units in a circuit)
        $meta = $this->extractMeta($rawData->first());
        $meta['captured_at'] = now()->toIso8601String();

        // Transform all units
        $units = $rawData->map(fn (array $row) => $this->transformUnit($row))->values()->all();

        // Compute summary statistics
        $summary = $this->computeSummary($units);

        return [
            'meta' => $meta,
            'summary' => $summary,
            'units' => $units,
        ];
    }

    /**
     * Extract metadata from first API row.
     */
    protected function extractMeta(array $row): array
    {
        $meta = [];

        foreach (self::META_FIELDS as $apiField => $normalizedField) {
            if (isset($row[$apiField]) && $row[$apiField] !== '') {
                $meta[$normalizedField] = $row[$apiField];
            }
        }

        return $meta;
    }

    /**
     * Transform a single unit row.
     */
    protected function transformUnit(array $row): array
    {
        $unit = [];

        foreach (self::UNIT_FIELD_MAP as $apiField => $normalizedField) {
            if (! array_key_exists($apiField, $row)) {
                continue;
            }

            $value = $row[$apiField];

            // Skip empty values to reduce JSON size
            if ($value === '' || $value === null) {
                continue;
            }

            // Normalize specific fields
            $unit[$normalizedField] = match ($normalizedField) {
                'trees' => (int) $value,
                'linear_ft', 'acres', 'lat', 'lng' => $this->normalizeDecimal($value),
                'assessed', 'audit_date' => $this->normalizeDate($value),
                'audit_pass' => (bool) $value,
                default => $value,
            };
        }

        return $unit;
    }

    /**
     * Compute summary statistics from transformed units.
     */
    protected function computeSummary(array $units): array
    {
        $totalTrees = 0;
        $totalLinearFt = 0;
        $totalAcres = 0;
        $byPermission = [];
        $byUnitType = [];
        $byStation = [];

        foreach ($units as $unit) {
            $totalTrees += $unit['trees'] ?? 0;
            $totalLinearFt += $unit['linear_ft'] ?? 0;
            $totalAcres += $unit['acres'] ?? 0;

            // Count by permission status
            $permission = $unit['permission'] ?? 'Unknown';
            $byPermission[$permission] = ($byPermission[$permission] ?? 0) + 1;

            // Count by unit type
            $type = $unit['type'] ?? 'Unknown';
            $byUnitType[$type] = ($byUnitType[$type] ?? 0) + 1;

            // Count by station
            $station = $unit['station'] ?? 'Unknown';
            $byStation[$station] = ($byStation[$station] ?? 0) + 1;
        }

        return [
            'total_units' => count($units),
            'total_trees' => $totalTrees,
            'total_linear_ft' => round($totalLinearFt, 2),
            'total_acres' => round($totalAcres, 4),
            'by_permission' => $byPermission,
            'by_unit_type' => $byUnitType,
            'by_station' => $byStation,
        ];
    }

    /**
     * Normalize decimal values.
     */
    protected function normalizeDecimal(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }

    /**
     * Normalize date values from API format.
     */
    protected function normalizeDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Handle /Date(...)/ format from API
        if (is_string($value) && preg_match('/\/Date\(([^)]+)\)\//', $value, $matches)) {
            $dateStr = $matches[1];

            // Handle ISO format dates inside /Date()/
            if (str_contains($dateStr, '-')) {
                return $dateStr;
            }

            // Handle timestamp format
            if (is_numeric($dateStr)) {
                return date('Y-m-d', (int) ($dateStr / 1000));
            }
        }

        // Return as-is if it looks like a date
        if (is_string($value) && strtotime($value) !== false) {
            return date('Y-m-d', strtotime($value));
        }

        return null;
    }

    /**
     * Generate hash of normalized content for deduplication.
     */
    public function generateHash(array $normalizedData): string
    {
        // Hash only the units array for deduplication
        // (meta changes like captured_at shouldn't trigger new snapshot)
        $unitsForHash = $normalizedData['units'] ?? [];

        // Sort units by ID for consistent hashing
        usort($unitsForHash, fn ($a, $b) => ($a['id'] ?? '') <=> ($b['id'] ?? ''));

        return hash('sha256', json_encode($unitsForHash));
    }

    /**
     * Return empty response structure.
     */
    protected function emptyResponse(): array
    {
        return [
            'meta' => [
                'captured_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'total_units' => 0,
                'total_trees' => 0,
                'total_linear_ft' => 0,
                'total_acres' => 0,
                'by_permission' => [],
                'by_unit_type' => [],
                'by_station' => [],
            ],
            'units' => [],
        ];
    }

    /**
     * Get quick stats from normalized data (for model columns).
     *
     * @return array{unit_count: int, total_trees: int, total_linear_ft: float, total_acres: float}
     */
    public function getQuickStats(array $normalizedData): array
    {
        $summary = $normalizedData['summary'] ?? [];

        return [
            'unit_count' => $summary['total_units'] ?? 0,
            'total_trees' => $summary['total_trees'] ?? 0,
            'total_linear_ft' => $summary['total_linear_ft'] ?? 0,
            'total_acres' => $summary['total_acres'] ?? 0,
        ];
    }
}
