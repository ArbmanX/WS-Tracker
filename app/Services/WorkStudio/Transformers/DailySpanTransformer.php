<?php

namespace App\Services\WorkStudio\Transformers;

use Illuminate\Support\Collection;

class DailySpanTransformer
{
    private const METERS_PER_MILE = 1609.34;

    /**
     * Transform raw station/unit data into grouped daily summaries.
     *
     * @param  Collection  $data  Raw station data from getAllUnits query
     * @return array Transformed data with daily groupings and totals
     */
    public function transform(Collection $data): array
    {
        $dailyGroups = [];
        $grandTotals = [
            'all_spans_feet' => 0,
            'all_spans_miles' => 0,
            'with_unit_feet' => 0,
            'with_unit_miles' => 0,
            'without_unit_feet' => 0,
            'without_unit_miles' => 0,
        ];

        // Track unique stations to avoid double-counting span lengths
        $processedStations = [];

        foreach ($data as $row) {
            $stationName = $row['Station_Name'];
            $spanLength = (float) ($row['Span_Length'] ?? 0);
            $unit = $row['Unit'];
            $assessedDate = $row['Assessed_Date'];

            // Only count each station's span length once for grand totals
            if (! isset($processedStations[$stationName])) {
                $processedStations[$stationName] = [
                    'span_length' => $spanLength,
                    'has_unit' => false,
                ];
                $grandTotals['all_spans_feet'] += $spanLength;
            }

            // Track if this station has any unit
            if ($unit !== null && $unit !== '') {
                $processedStations[$stationName]['has_unit'] = true;
            }

            // Group by assessed date (skip if no date)
            if ($assessedDate !== null && $assessedDate !== '') {
                $dateKey = $assessedDate;

                if (! isset($dailyGroups[$dateKey])) {
                    $dailyGroups[$dateKey] = [
                        'stations' => [],
                        'unit_list' => [],
                        'day_total_feet' => 0,
                        'day_total_miles' => 0,
                    ];
                }

                // Add station with span length as value (overwrites if same station appears multiple times per day)
                if (! isset($dailyGroups[$dateKey]['stations'][$stationName])) {
                    $dailyGroups[$dateKey]['stations'][$stationName] = $spanLength;
                    $dailyGroups[$dateKey]['day_total_feet'] += $spanLength;
                }

                // Add unit to list (duplicates allowed)
                if ($unit !== null && $unit !== '') {
                    $dailyGroups[$dateKey]['unit_list'][] = $unit;
                }
            }
        }

        // Calculate with_unit and without_unit totals
        foreach ($processedStations as $station) {
            if ($station['has_unit']) {
                $grandTotals['with_unit_feet'] += $station['span_length'];
            } else {
                $grandTotals['without_unit_feet'] += $station['span_length'];
            }
        }

        // Convert feet to miles for grand totals
        $grandTotals['all_spans_miles'] = round($grandTotals['all_spans_feet'] / self::METERS_PER_MILE, 4);
        $grandTotals['with_unit_miles'] = round($grandTotals['with_unit_feet'] / self::METERS_PER_MILE, 4);
        $grandTotals['without_unit_miles'] = round($grandTotals['without_unit_feet'] / self::METERS_PER_MILE, 4);

        // Convert feet to miles for daily totals and sort by date descending
        foreach ($dailyGroups as $date => &$group) {
            $group['day_total_miles'] = round($group['day_total_feet'] / self::METERS_PER_MILE, 4);
        }
        unset($group);

        // Sort by date descending
        krsort($dailyGroups);

        return [
            'daily' => $dailyGroups,
            'totals' => $grandTotals,
            'station_count' => count($processedStations),
        ];
    }
}
