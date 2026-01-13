<?php

namespace App\Services\WorkStudio\Transformers;

use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Transform WorkStudio vegetation assessment data to Circuit model format.
 *
 * Maps API field names to database columns and extracts relevant data.
 */
class CircuitTransformer
{
    /**
     * API field name to database column mapping.
     */
    private const FIELD_MAP = [
        // Core identification
        'SS_JOBGUID' => 'job_guid',
        'SS_WO' => 'work_order',
        'SS_EXT' => 'extension',
        'SS_TITLE' => 'title',

        // Status and type
        'WSREQ_STATUS' => 'api_status',
        'SS_JOBTYPE' => 'job_type',
        'VEGJOB_CYCLETYPE' => 'cycle_type',

        // Regional info
        'REGION' => 'region_name',

        // Metrics
        'VEGJOB_LENGTH' => 'total_miles',
        'VEGJOB_LENGTHCOMP' => 'miles_planned',
        'VEGJOB_PRCENT' => 'percent_complete',
        'VEGJOB_PROJACRES' => 'projected_acres',
        'UNITCOUNTS_LENGTHWRK' => 'total_linear_ft',
        'UNITCOUNTS_NUMTREES' => 'total_trees',

        // People
        'VEGJOB_FORESTER' => 'forester_name',
        'SS_ASSIGNEDTO' => 'assigned_to',
        'SS_TAKENBY' => 'taken_by',
        'VEGJOB_CONTRACTOR' => 'contractor',
        'VEGJOB_GF' => 'general_foreman',

        // Dates
        'SS_EDITDATE' => 'api_modified_date',
        'WPStartDate_Assessment_Xrefs_WP_STARTDATE' => 'work_plan_start_date',

        // Other
        'VEGJOB_LINENAME' => 'line_name',
        'VEGJOB_CIRCCOMNTS' => 'comments',
        'VEGJOB_COSTMETHOD' => 'cost_method',
        'WSREQ_BOUNDSGEOM' => 'bounds_geometry',
    ];

    /**
     * Transform a collection of raw API rows to circuit data.
     */
    public function transformCollection(Collection $rawData): Collection
    {
        return $rawData->map(fn (array $row) => $this->transform($row));
    }

    /**
     * Transform a single API row to circuit model format.
     */
    public function transform(array $apiRow): array
    {
        $mapped = [];

        // Map known fields
        foreach (self::FIELD_MAP as $apiField => $dbColumn) {
            if (array_key_exists($apiField, $apiRow)) {
                $mapped[$dbColumn] = $apiRow[$apiField];
            }
        }

        // Transform region name to ID
        if (isset($mapped['region_name'])) {
            $mapped['region_id'] = $this->resolveRegionId($mapped['region_name']);
            unset($mapped['region_name']);
        }

        // Normalize extension - default to '@' if empty
        $mapped['extension'] = $this->normalizeExtension($mapped['extension'] ?? null);

        // Ensure numeric fields are properly typed
        $mapped = $this->normalizeNumericFields($mapped);

        // Store full API response as JSON for reference
        $mapped['api_data_json'] = $this->extractApiDataJson($apiRow);

        // Handle dates - ensure they're Carbon instances or null
        $mapped = $this->normalizeDateFields($mapped);

        return $mapped;
    }

    /**
     * Resolve region name to region ID.
     */
    protected function resolveRegionId(string $regionName): ?int
    {
        $regions = Cache::remember('regions.by_name', 3600, function () {
            return Region::pluck('id', 'name')->toArray();
        });

        return $regions[$regionName] ?? null;
    }

    /**
     * Normalize extension value.
     */
    protected function normalizeExtension(?string $extension): string
    {
        if (empty($extension) || $extension === '@') {
            return '@';
        }

        return trim($extension);
    }

    /**
     * Ensure numeric fields are properly typed.
     */
    protected function normalizeNumericFields(array $mapped): array
    {
        $numericFields = [
            'total_miles',
            'miles_planned',
            'percent_complete',
            'projected_acres',
            'total_linear_ft',
            'total_trees',
        ];

        foreach ($numericFields as $field) {
            if (isset($mapped[$field])) {
                $mapped[$field] = is_numeric($mapped[$field])
                    ? (float) $mapped[$field]
                    : 0;
            }
        }

        return $mapped;
    }

    /**
     * Normalize date fields to Carbon instances.
     */
    protected function normalizeDateFields(array $mapped): array
    {
        $dateFields = ['api_modified_date', 'work_plan_start_date'];

        foreach ($dateFields as $field) {
            if (isset($mapped[$field])) {
                $mapped[$field] = $this->ensureCarbon($mapped[$field]);
            }
        }

        return $mapped;
    }

    /**
     * Ensure a value is a Carbon instance or null.
     */
    protected function ensureCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value) && ! empty($value)) {
            try {
                return Carbon::parse($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Extract relevant API data to store as JSON.
     */
    protected function extractApiDataJson(array $apiRow): array
    {
        // Store commonly needed API fields that aren't directly mapped
        $fieldsToStore = [
            'VEGJOB_SERVCOMP',
            'VEGJOB_OPCO',
            'SS_READONLY',
            'SS_ITEMTYPELIST',
            'WSREQ_VERSION',
            'WSREQ_SYNCHVERSN',
            'WSREQ_COORDSYS',
            'WSREQ_SKETCHLEFT',
            'WSREQ_SKETCHTOP',
            'WSREQ_SKETCHBOTM',
            'WSREQ_SKETCHRITE',
        ];

        $json = [];
        foreach ($fieldsToStore as $field) {
            if (array_key_exists($field, $apiRow)) {
                $json[$field] = $apiRow[$field];
            }
        }

        return $json;
    }

    /**
     * Extract planner usernames from API response.
     * Returns an array of planner identifiers found in the circuit data.
     */
    public function extractPlanners(array $apiRow): array
    {
        $planners = [];

        // Primary planner from forester field
        if (! empty($apiRow['VEGJOB_FORESTER'])) {
            $planners[] = trim($apiRow['VEGJOB_FORESTER']);
        }

        // Assigned user
        if (! empty($apiRow['SS_ASSIGNEDTO'])) {
            $assignedTo = trim($apiRow['SS_ASSIGNEDTO']);
            if (! in_array($assignedTo, $planners)) {
                $planners[] = $assignedTo;
            }
        }

        // Taken by user
        if (! empty($apiRow['SS_TAKENBY'])) {
            $takenBy = trim($apiRow['SS_TAKENBY']);
            if (! in_array($takenBy, $planners)) {
                $planners[] = $takenBy;
            }
        }

        return array_filter($planners);
    }

    /**
     * Check if this circuit is a split assessment child.
     */
    public function isSplitChild(array $apiRow): bool
    {
        $extension = $apiRow['SS_EXT'] ?? '@';

        return $extension !== '@' && $extension !== '';
    }

    /**
     * Get the parent work order for a split assessment.
     */
    public function getParentWorkOrder(array $apiRow): ?string
    {
        if (! $this->isSplitChild($apiRow)) {
            return null;
        }

        return $apiRow['SS_WO'] ?? null;
    }

    /**
     * Clear the region cache (useful after seeding).
     */
    public function clearRegionCache(): void
    {
        Cache::forget('regions.by_name');
    }
}
