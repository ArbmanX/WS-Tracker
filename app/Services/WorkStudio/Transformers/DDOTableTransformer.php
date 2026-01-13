<?php

namespace App\Services\WorkStudio\Transformers;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Transform WorkStudio DDOTable format to Laravel Collection.
 *
 * DDOTable format uses separate Heading and Data arrays:
 * {
 *   "Protocol": "DATASET",
 *   "DataSet": {
 *     "Heading": ["Column1", "Column2", ...],
 *     "Data": [
 *       [value1, value2, ...],
 *       [value1, value2, ...],
 *     ]
 *   }
 * }
 */
class DDOTableTransformer
{
    /**
     * Transform a DDOTable response into a Collection of associative arrays.
     */
    public function transform(array $response): Collection
    {
        if (! $this->isValidResponse($response)) {
            return collect();
        }

        $headings = $response['DataSet']['Heading'];
        $data = $response['DataSet']['Data'];

        return collect($data)->map(function (array $row) use ($headings) {
            $mapped = array_combine($headings, $row);

            return $this->transformRow($mapped);
        });
    }

    /**
     * Check if response has valid DDOTable structure.
     */
    public function isValidResponse(array $response): bool
    {
        return isset($response['Protocol'])
            && $response['Protocol'] === 'DATASET'
            && isset($response['DataSet']['Heading'])
            && isset($response['DataSet']['Data'])
            && is_array($response['DataSet']['Heading'])
            && is_array($response['DataSet']['Data']);
    }

    /**
     * Transform a single row, parsing special field types.
     */
    protected function transformRow(array $row): array
    {
        foreach ($row as $key => $value) {
            // Parse WorkStudio date format: "/Date(2025-12-05)/" or "/Date(2025-12-05T20:12:44.142Z)/"
            if (is_string($value) && preg_match('#^/Date\((.+?)\)/$#', $value, $matches)) {
                $row[$key] = $this->parseDate($matches[1]);
            }

            // Parse geometry objects (keep as-is but mark type)
            if (is_array($value) && isset($value['@sourceFormat']) && $value['@sourceFormat'] === 'DataObjectGeometry') {
                $row[$key] = $this->transformGeometry($value);
            }
        }

        return $row;
    }

    /**
     * Parse WorkStudio date string to Carbon instance or null.
     */
    protected function parseDate(string $dateString): ?Carbon
    {
        try {
            $date = Carbon::parse($dateString);

            // Handle invalid/placeholder dates like "1899-12-30"
            if ($date->year < 1900) {
                return null;
            }

            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Transform geometry object for storage.
     */
    protected function transformGeometry(array $geometry): array
    {
        return [
            'type' => $geometry['type'] ?? 'Unknown',
            'coordinates' => $geometry['coordinates'] ?? [],
            '_is_geometry' => true,
        ];
    }

    /**
     * Extract only the headings from a response.
     */
    public function getHeadings(array $response): array
    {
        return $response['DataSet']['Heading'] ?? [];
    }

    /**
     * Get the count of rows in a response.
     */
    public function getRowCount(array $response): int
    {
        return count($response['DataSet']['Data'] ?? []);
    }

    /**
     * Extract specific columns from a transformed collection.
     */
    public function selectColumns(Collection $data, array $columns): Collection
    {
        return $data->map(fn (array $row) => array_intersect_key($row, array_flip($columns)));
    }
}
