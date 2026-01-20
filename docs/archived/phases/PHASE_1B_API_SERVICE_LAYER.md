# Phase 1B: API Service Layer

> **Goal:** Create the WorkStudio API integration layer with transformers and credential management.
> **Estimated Time:** 2 days
> **Dependencies:** Phase 1A complete (models exist for credential storage)

---

## Status: ✅ COMPLETE

| Item | Status | Notes |
|------|--------|-------|
| WorkStudioApiService | ✅ Done | HTTP client with retry logic |
| ApiCredentialManager | ✅ Done | Credential rotation |
| DDOTableTransformer | ✅ Done | Generic response parser |
| CircuitTransformer | ✅ Done | Circuit field mapping |
| AggregateCalculationService | ✅ Done | Compute aggregates from raw API |
| DateParser helper | ✅ Done | Parse WorkStudio date formats (in CircuitTransformer) |

---

## Architecture Overview

```
WorkStudio API
      │
      ▼
WorkStudioApiService (HTTP client, auth, retry)
      │
      ▼
DDOTableTransformer (parse DDOTable format)
      │
      ▼
CircuitTransformer / PlannedUnitAggregateTransformer
      │
      ▼
AggregateCalculationService (compute totals)
      │
      ▼
AggregateStorageService (persist to database)
```

---

## Checklist

### Contracts
- [x] Create WorkStudioApiInterface contract

### Core Services
- [x] Create WorkStudioApiService
  - `healthCheck(): bool`
  - `getViewData(viewGuid, filter, userId): array`
  - `executeWithRetry(callable, userId): array`
- [x] Create ApiCredentialManager
  - `getCredentials(userId): array`
  - `rotateCredential(): array`
  - `markFailed(userId): void`
  - `markSuccess(userId): void`

### Transformers
- [x] Create DDOTableTransformer (Heading + Data → Collection)
- [x] Create CircuitTransformer with field mappings
- [x] Create PlannedUnitAggregateTransformer

### Aggregation Services
- [x] Create AggregateCalculationService
  - `computeFromApiResponse(Collection): array`
- [x] Create AggregateStorageService
  - `storeCircuitAggregates(circuitId, aggregates): void`
- [x] Create AggregateDiffService
  - `compare(old, new): array`
- [x] Create AggregateQueryService
  - `getByCircuit(circuitId, dateRange): Collection`
  - `getByPlanner(userId, dateRange): Collection`
  - `getByRegion(regionId, dateRange): Collection`

### Helpers
- [x] Create DateParser helper for `/Date(...)/ formats (integrated in CircuitTransformer)

### Exceptions
- [ ] Create WorkStudioApiException
- [ ] Create WorkStudioAuthException
- [ ] Create WorkStudioTimeoutException

### Service Provider
- [x] Create WorkStudioServiceProvider
- [x] Register in `bootstrap/providers.php`

### Tests
- [x] Write unit tests for all transformers
- [x] Write unit tests for credential manager
- [x] Write unit tests for aggregation services

---

## File Structure

```
app/
├── Services/
│   └── WorkStudio/
│       ├── Contracts/
│       │   └── WorkStudioApiInterface.php
│       ├── WorkStudioApiService.php
│       ├── ApiCredentialManager.php
│       ├── Transformers/
│       │   ├── DDOTableTransformer.php
│       │   ├── CircuitTransformer.php
│       │   └── PlannedUnitAggregateTransformer.php
│       └── Aggregation/
│           ├── AggregateCalculationService.php
│           ├── AggregateStorageService.php
│           ├── AggregateDiffService.php
│           └── AggregateQueryService.php
├── Support/
│   └── Helpers/
│       └── DateParser.php
├── Exceptions/
│   ├── WorkStudioApiException.php
│   ├── WorkStudioAuthException.php
│   └── WorkStudioTimeoutException.php
└── Providers/
    └── WorkStudioServiceProvider.php
```

---

## Key Implementation Details

### WorkStudioApiService

```php
<?php

namespace App\Services\WorkStudio;

use Illuminate\Support\Facades\Http;

class WorkStudioApiService implements Contracts\WorkStudioApiInterface
{
    private const TIMEOUT = 60;
    private const MAX_RETRIES = 5;

    public function __construct(
        private ApiCredentialManager $credentialManager
    ) {}

    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(10)
                ->get(str_replace('/ddoprotocol/', '', config('workstudio.base_url')));
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getViewData(string $viewGuid, array $filter, ?int $userId = null): array
    {
        $credentials = $this->credentialManager->getCredentials($userId);

        return $this->executeWithRetry(function () use ($viewGuid, $filter, $credentials) {
            return Http::timeout(self::TIMEOUT)
                ->withBasicAuth($credentials['username'], $credentials['password'])
                ->post(config('workstudio.base_url'), [
                    'protocol' => 'GETVIEWDATA',
                    'ViewDefinitionGuid' => $viewGuid,
                    'ViewFilter' => $filter,
                    'ResultFormat' => 'DDOTable',
                ]);
        }, $credentials['user_id']);
    }

    private function executeWithRetry(callable $request, ?int $userId): array
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < self::MAX_RETRIES) {
            try {
                $response = $request();

                if ($response->status() === 401) {
                    $this->credentialManager->markFailed($userId);
                    throw new \App\Exceptions\WorkStudioAuthException('Authentication failed');
                }

                $this->credentialManager->markSuccess($userId);
                return $response->json();

            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;
                sleep(pow(2, $attempts)); // Exponential backoff
            }
        }

        throw $lastException;
    }
}
```

### DDOTableTransformer

```php
<?php

namespace App\Services\WorkStudio\Transformers;

use Illuminate\Support\Collection;

class DDOTableTransformer
{
    public function transform(array $response): Collection
    {
        if (!isset($response['DataSet']['Heading'], $response['DataSet']['Data'])) {
            return collect();
        }

        $headings = $response['DataSet']['Heading'];
        $data = $response['DataSet']['Data'];

        return collect($data)->map(function ($row) use ($headings) {
            return array_combine($headings, $row);
        });
    }
}
```

### AggregateCalculationService

```php
<?php

namespace App\Services\WorkStudio\Aggregation;

use App\Models\UnitType;
use Illuminate\Support\Collection;

class AggregateCalculationService
{
    public function computeFromApiResponse(Collection $rawUnits): array
    {
        $linearFtCodes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_LINEAR_FT);
        $acresCodes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_ACRES);
        $treeCountCodes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_TREE_COUNT);

        return [
            'total_units' => $rawUnits->count(),
            'total_linear_ft' => $rawUnits
                ->whereIn('VEGUNIT_UNIT', $linearFtCodes)
                ->sum('JOBVEGETATIONUNITS_LENGTHWRK'),
            'total_acres' => $rawUnits
                ->whereIn('VEGUNIT_UNIT', $acresCodes)
                ->sum('JOBVEGETATIONUNITS_ACRES'),
            'total_trees' => $rawUnits
                ->whereIn('VEGUNIT_UNIT', $treeCountCodes)
                ->sum('JOBVEGETATIONUNITS_NUMTREES'),
            'units_approved' => $rawUnits->where('VEGUNIT_PERMSTAT', 'Approved')->count(),
            'units_refused' => $rawUnits->where('VEGUNIT_PERMSTAT', 'Refused')->count(),
            'units_pending' => $rawUnits
                ->where('VEGUNIT_PERMSTAT', '')
                ->orWhereNull('VEGUNIT_PERMSTAT')
                ->count(),
            'unit_counts_by_type' => $rawUnits->countBy('VEGUNIT_UNIT')->toArray(),
            'planner_distribution' => $rawUnits->countBy('VEGUNIT_FORESTER')->toArray(),
        ];
    }
}
```

### DateParser Helper

```php
<?php

namespace App\Support\Helpers;

use Carbon\Carbon;

class DateParser
{
    /**
     * Parse WorkStudio date formats like:
     * - /Date(2025-01-10)/
     * - /Date(2025-01-10T14:30:00.000Z)/
     */
    public static function parse(?string $dateString): ?Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        // Extract date from /Date(...)/ format
        if (preg_match('/\/Date\(([^)]+)\)\//', $dateString, $matches)) {
            $dateString = $matches[1];
        }

        try {
            $date = Carbon::parse($dateString);

            // Ignore dates before 2010 (treat as null)
            if ($date->year < 2010) {
                return null;
            }

            return $date->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }
}
```

---

## Field Mappings

### Circuit Transformer Mappings

| API Column | Model Field |
|------------|-------------|
| `SS_JOBGUID` | `job_guid` |
| `SS_WO` | `work_order` |
| `SS_EXT` | `extension` |
| `REGION` | `region_id` (lookup) |
| `SS_TITLE` | `title` |
| `VEGJOB_LENGTH` | `total_miles` |
| `VEGJOB_LENGTHCOMP` | `miles_planned` |
| `VEGJOB_PRCENT` | `percent_complete` |
| `VEGJOB_PROJACRES` | `total_acres` |
| `WPStartDate_Assessment_Xrefs_WP_STARTDATE` | `start_date` |
| `SS_EDITDATE` | `api_modified_date` |
| `WSREQ_STATUS` | `api_status` |
| `SS_TAKENBY` | planner link |
| `VEGJOB_CONTRACTOR` | `contractor` |
| `VEGJOB_CYCLETYPE` | `cycle_type` |

---

## Testing Requirements

```php
// tests/Unit/Transformers/DDOTableTransformerTest.php
it('transforms DDOTable format to collection', function () {
    $response = [
        'DataSet' => [
            'Heading' => ['ID', 'NAME', 'VALUE'],
            'Data' => [
                [1, 'Test', 100],
                [2, 'Test2', 200],
            ],
        ],
    ];

    $transformer = new DDOTableTransformer();
    $result = $transformer->transform($response);

    expect($result)->toHaveCount(2);
    expect($result->first())->toBe(['ID' => 1, 'NAME' => 'Test', 'VALUE' => 100]);
});

it('handles empty data array', function () {
    $transformer = new DDOTableTransformer();
    $result = $transformer->transform(['DataSet' => ['Heading' => [], 'Data' => []]]);

    expect($result)->toBeEmpty();
});

// tests/Unit/Transformers/CircuitTransformerTest.php
it('maps API columns to model fields', function () {...});
it('parses WorkStudio date format', function () {...});
it('extracts region from API response', function () {...});

// tests/Unit/Services/ApiCredentialManagerTest.php
it('rotates through verified credentials', function () {...});
it('marks failed credentials', function () {...});
it('falls back to service account', function () {...});

// tests/Unit/Services/AggregateCalculationServiceTest.php
it('computes totals from raw API data', function () {...});
it('groups by unit type', function () {...});
it('counts permission statuses', function () {...});

// tests/Unit/Helpers/DateParserTest.php
it('parses standard date format', function () {
    $result = DateParser::parse('/Date(2025-01-10)/');
    expect($result->format('Y-m-d'))->toBe('2025-01-10');
});

it('parses datetime format', function () {
    $result = DateParser::parse('/Date(2025-01-10T14:30:00.000Z)/');
    expect($result->format('Y-m-d'))->toBe('2025-01-10');
});

it('ignores dates before 2010', function () {
    $result = DateParser::parse('/Date(1900-01-01)/');
    expect($result)->toBeNull();
});
```

---

## Next Phase

Once all items are checked, proceed to **[Phase 1C: Sync Jobs](./PHASE_1C_SYNC_JOBS.md)**.
