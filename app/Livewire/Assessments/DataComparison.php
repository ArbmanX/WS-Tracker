<?php

namespace App\Livewire\Assessments;

use App\Models\Circuit;
use App\Services\WorkStudio\WorkStudioApiService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Data Comparison - API vs DB'])]
class DataComparison extends Component
{
    #[Url]
    public int $limit = 10;

    #[Url]
    public string $domainFilter = 'ASPLUNDH';

    public bool $loading = false;

    public array $apiData = [];

    public array $selectedFields = [];

    /**
     * All available API fields from a circuit response.
     */
    public const API_FIELDS = [
        'SS_JOBGUID' => ['label' => 'Job GUID', 'db_column' => 'job_guid', 'category' => 'identification'],
        'SS_WO' => ['label' => 'Work Order', 'db_column' => 'work_order', 'category' => 'identification'],
        'SS_EXT' => ['label' => 'Extension', 'db_column' => 'extension', 'category' => 'identification'],
        'SS_TITLE' => ['label' => 'Title', 'db_column' => 'title', 'category' => 'identification'],
        'WSREQ_STATUS' => ['label' => 'API Status', 'db_column' => 'api_status', 'category' => 'status'],
        'SS_JOBTYPE' => ['label' => 'Job Type', 'db_column' => null, 'category' => 'status'],
        'VEGJOB_CYCLETYPE' => ['label' => 'Cycle Type', 'db_column' => 'cycle_type', 'category' => 'status'],
        'REGION' => ['label' => 'Region', 'db_column' => 'region_id', 'category' => 'location'],
        'VEGJOB_LENGTH' => ['label' => 'Total Miles', 'db_column' => 'total_miles', 'category' => 'metrics'],
        'VEGJOB_LENGTHCOMP' => ['label' => 'Completed Miles', 'db_column' => 'miles_planned', 'category' => 'metrics'],
        'VEGJOB_PRCENT' => ['label' => 'Percent Complete', 'db_column' => 'percent_complete', 'category' => 'metrics'],
        'VEGJOB_PROJACRES' => ['label' => 'Projected Acres', 'db_column' => 'total_acres', 'category' => 'metrics'],
        'UNITCOUNTS_LENGTHWRK' => ['label' => 'Work Length (ft)', 'db_column' => null, 'category' => 'metrics'],
        'UNITCOUNTS_NUMTREES' => ['label' => 'Tree Count', 'db_column' => null, 'category' => 'metrics'],
        'SS_EDITDATE' => ['label' => 'Last Modified', 'db_column' => 'api_modified_at', 'category' => 'dates'],
        'WPStartDate_Assessment_Xrefs_WP_STARTDATE' => ['label' => 'Work Plan Start', 'db_column' => 'start_date', 'category' => 'dates'],
        'VEGJOB_FORESTER' => ['label' => 'Forester', 'db_column' => null, 'category' => 'people'],
        'SS_ASSIGNEDTO' => ['label' => 'Assigned To', 'db_column' => null, 'category' => 'people'],
        'SS_TAKENBY' => ['label' => 'Taken By (Planner)', 'db_column' => 'taken_by', 'category' => 'people'],
        'VEGJOB_CONTRACTOR' => ['label' => 'Contractor', 'db_column' => 'contractor', 'category' => 'people'],
        'VEGJOB_GF' => ['label' => 'General Foreman', 'db_column' => null, 'category' => 'people'],
        'VEGJOB_LINENAME' => ['label' => 'Line Name', 'db_column' => null, 'category' => 'identification'],
        'VEGJOB_CIRCCOMNTS' => ['label' => 'Comments', 'db_column' => null, 'category' => 'other'],
        'VEGJOB_COSTMETHOD' => ['label' => 'Cost Method', 'db_column' => null, 'category' => 'other'],
        'WSREQ_VERSION' => ['label' => 'Version', 'db_column' => 'ws_version', 'category' => 'sync'],
        'WSREQ_SYNCHVERSN' => ['label' => 'Sync Version', 'db_column' => 'ws_sync_version', 'category' => 'sync'],
        'WSREQ_SYNCHSTATE' => ['label' => 'Sync State', 'db_column' => null, 'category' => 'sync'],
        'VEGJOB_SERVCOMP' => ['label' => 'Service Company', 'db_column' => null, 'category' => 'other'],
        'VEGJOB_OPCO' => ['label' => 'Operating Company', 'db_column' => null, 'category' => 'other'],
    ];

    public function mount(): void
    {
        // Default selected fields - focus on metrics and dates for planner analytics
        $this->selectedFields = [
            'SS_WO',
            'SS_TITLE',
            'SS_TAKENBY',
            'VEGJOB_LENGTH',
            'VEGJOB_LENGTHCOMP',
            'VEGJOB_PRCENT',
            'SS_EDITDATE',
            'WSREQ_STATUS',
        ];
    }

    public function fetchApiData(): void
    {
        $this->loading = true;

        try {
            $api = app(WorkStudioApiService::class);
            $rawCircuits = $api->getCircuitsByStatus('ACTIV');

            // Filter by domain if specified
            if ($this->domainFilter) {
                $rawCircuits = $rawCircuits->filter(function ($circuit) {
                    $takenBy = $circuit['api_data_json']['SS_TAKENBY'] ?? '';

                    return str_starts_with($takenBy, $this->domainFilter.'\\');
                });
            }

            // Limit results
            $this->apiData = $rawCircuits->take($this->limit)->values()->toArray();
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to fetch API data: '.$e->getMessage(), type: 'error');
            $this->apiData = [];
        }

        $this->loading = false;
    }

    #[Computed]
    public function dbCircuits(): Collection
    {
        $workOrders = collect($this->apiData)->pluck('work_order')->filter()->unique();

        if ($workOrders->isEmpty()) {
            return collect();
        }

        return Circuit::whereIn('work_order', $workOrders)
            ->with('region')
            ->get()
            ->keyBy('work_order');
    }

    #[Computed]
    public function fieldCategories(): array
    {
        $categories = [];
        foreach (self::API_FIELDS as $field => $config) {
            $categories[$config['category']][$field] = $config;
        }

        return $categories;
    }

    #[Computed]
    public function comparisonData(): array
    {
        $comparison = [];

        foreach ($this->apiData as $apiCircuit) {
            $workOrder = $apiCircuit['work_order'] ?? null;
            $dbCircuit = $this->dbCircuits[$workOrder] ?? null;

            $row = [
                'work_order' => $workOrder,
                'has_db_record' => $dbCircuit !== null,
                'fields' => [],
            ];

            foreach ($this->selectedFields as $apiField) {
                $config = self::API_FIELDS[$apiField] ?? null;
                if (! $config) {
                    continue;
                }

                $apiValue = $this->getApiValue($apiCircuit, $apiField);
                $dbValue = $dbCircuit ? $this->getDbValue($dbCircuit, $config['db_column'], $apiField) : null;

                $row['fields'][$apiField] = [
                    'label' => $config['label'],
                    'api_value' => $apiValue,
                    'db_value' => $dbValue,
                    'stored' => $config['db_column'] !== null,
                    'matches' => $this->valuesMatch($apiValue, $dbValue),
                ];
            }

            $comparison[] = $row;
        }

        return $comparison;
    }

    protected function getApiValue(array $apiCircuit, string $field): mixed
    {
        // Check direct field first
        if (array_key_exists($field, $apiCircuit)) {
            return $apiCircuit[$field];
        }

        // Check in api_data_json
        return $apiCircuit['api_data_json'][$field] ?? null;
    }

    protected function getDbValue(?Circuit $circuit, ?string $dbColumn, string $apiField): mixed
    {
        if (! $circuit || ! $dbColumn) {
            // Try to get from api_data_json stored in DB
            if ($circuit && isset($circuit->api_data_json[$apiField])) {
                return $circuit->api_data_json[$apiField].' (json)';
            }

            return null;
        }

        $value = $circuit->{$dbColumn};

        // Handle region specially
        if ($dbColumn === 'region_id' && $circuit->region) {
            return $circuit->region->name;
        }

        return $value;
    }

    protected function valuesMatch(mixed $apiValue, mixed $dbValue): bool
    {
        if ($dbValue === null) {
            return false;
        }

        // Remove " (json)" suffix for comparison
        if (is_string($dbValue)) {
            $dbValue = str_replace(' (json)', '', $dbValue);
        }

        // Loose comparison for numeric values
        if (is_numeric($apiValue) && is_numeric($dbValue)) {
            return abs((float) $apiValue - (float) $dbValue) < 0.01;
        }

        // String comparison
        return (string) $apiValue === (string) $dbValue;
    }

    public function toggleField(string $field): void
    {
        if (in_array($field, $this->selectedFields)) {
            $this->selectedFields = array_values(array_diff($this->selectedFields, [$field]));
        } else {
            $this->selectedFields[] = $field;
        }

        unset($this->comparisonData);
    }

    public function selectAllInCategory(string $category): void
    {
        foreach (self::API_FIELDS as $field => $config) {
            if ($config['category'] === $category && ! in_array($field, $this->selectedFields)) {
                $this->selectedFields[] = $field;
            }
        }

        unset($this->comparisonData);
    }

    public function render()
    {
        return view('livewire.assessments.data-comparison');
    }
}
