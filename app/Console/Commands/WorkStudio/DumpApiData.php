<?php

namespace App\Console\Commands\WorkStudio;

use App\Services\WorkStudio\ApiCredentialManager;
use App\Services\WorkStudio\Transformers\CircuitTransformer;
use App\Services\WorkStudio\Transformers\DDOTableTransformer;
use App\Services\WorkStudio\Transformers\PlannedUnitAggregateTransformer;
use App\Services\WorkStudio\WorkStudioApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DumpApiData extends Command
{
    protected $signature = 'ws:dump
                            {type=circuits : Data type to fetch (circuits, units, health, raw)}
                            {--status=ACTIV : Circuit status filter (ACTIV, QC, REWRK, CLOSE)}
                            {--work-order= : Work order number for units (e.g., 2025-1234)}
                            {--limit=0 : Limit number of records (0 = no limit)}
                            {--raw : Show raw DDOTable data before transformation}
                            {--json : Output as JSON instead of table}
                            {--output= : Save output to file (path)}
                            {--pretty : Pretty print JSON output}';

    protected $description = 'Fetch and display WorkStudio API data for verification';

    public function __construct(
        private WorkStudioApiService $api,
        private ApiCredentialManager $credentialManager,
        private DDOTableTransformer $ddoTransformer,
        private PlannedUnitAggregateTransformer $unitTransformer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {

        $type = $this->argument('type');

        return match ($type) {
            'health' => $this->checkHealth(),
            'circuits' => $this->fetchCircuits(),
            'units' => $this->fetchUnits(),
            'raw' => $this->fetchRawView(),
            default => $this->error("Unknown type: {$type}. Use: circuits, units, health, raw") ?? 1,
        };
    }

    private function checkHealth(): int
    {
        $this->info('Checking WorkStudio API health...');

        $credInfo = $this->credentialManager->getCredentialsInfo();
        $this->table(['Setting', 'Value'], [
            ['Base URL', config('workstudio.base_url').'GETVIEWDATA'],
            ['Credential Type', $credInfo['type']],
            ['Username', $credInfo['username'] ?? 'N/A'],
        ]);

        $this->newLine();

        try {
            $healthy = $this->api->healthCheck();

            if ($healthy) {
                $this->info('✓ API is reachable');

                return Command::SUCCESS;
            }

            $this->error('✗ API returned error status');

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('✗ API health check failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function fetchCircuits(): int
    {
        $status = $this->option('status');
        $limit = (int) $this->option('limit');

        $this->info("Fetching circuits with status: {$status}");
        $this->info('This may take a moment...');
        $this->newLine();

        try {
            if ($this->option('raw')) {
                return $this->fetchRawCircuits($status, $limit);
            }

            $circuits = $this->api->getCircuitsByStatus($status);

            if ($limit > 0) {
                $circuits = $circuits->take($limit);
            }

            $this->info("Retrieved {$circuits->count()} circuit(s)");
            $this->newLine();

            return $this->outputData($circuits->toArray(), 'circuits');
        } catch (\Exception $e) {
            $this->error('Failed to fetch circuits: '.$e->getMessage());

            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    private function fetchRawCircuits(string $status, int $limit): int
    {
        $response = $this->makeRawRequest(
            config('workstudio.views.vegetation_assessments'),
            [
                'FilterName' => 'By Job Status',
                'FilterValue' => $status,
                'FilterCaption' => 'In Progress',
            ]
        );

        // $this->info(
        //     (new CircuitTransformer)->transformCollection(collect($response))
        // );
        // exit;'
        // Carbon\Carbon::setTimezone('America/New_York');

        if (! $response) {
            return Command::FAILURE;
        }

        $this->showRawResponse($response, $limit);

        return Command::SUCCESS;
    }

    private function fetchUnits(): int
    {
        $workOrder = $this->option('work-order');

        if (! $workOrder) {
            $this->error('--work-order is required for units type');
            $this->info('Example: php artisan ws:dump units --work-order=2025-1234');

            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');

        $this->info("Fetching planned units for work order: {$workOrder}");
        $this->info('This may take a moment...');
        $this->newLine();

        try {
            if ($this->option('raw')) {
                return $this->fetchRawUnits($workOrder, $limit);
            }

            $units = $this->api->getPlannedUnits($workOrder);

            if ($limit > 0) {
                $units = $units->take($limit);
            }

            $this->info("Retrieved {$units->count()} unit(s)");

            // Show aggregate summary
            if ($units->isNotEmpty()) {
                $this->newLine();
                $this->info('=== Aggregate Summary ===');
                $aggregate = $this->unitTransformer->transformToAggregate($units);
                $this->table(['Metric', 'Value'], [
                    ['Total Units', $aggregate['total_units']],
                    ['Total Linear Ft', number_format($aggregate['total_linear_ft'], 2)],
                    ['Total Acres', number_format($aggregate['total_acres'], 4)],
                    ['Total Trees', $aggregate['total_trees']],
                    ['Units Approved', $aggregate['units_approved']],
                    ['Units Refused', $aggregate['units_refused']],
                    ['Units Pending', $aggregate['units_pending']],
                ]);

                if (! empty($aggregate['unit_counts_by_type'])) {
                    $this->newLine();
                    $this->info('=== Unit Counts by Type ===');
                    $this->table(
                        ['Type', 'Count'],
                        collect($aggregate['unit_counts_by_type'])->map(fn ($count, $type) => [$type, $count])->toArray()
                    );
                }

                if (! empty($aggregate['planner_distribution'])) {
                    $this->newLine();
                    $this->info('=== Planner Distribution ===');
                    $plannerRows = collect($aggregate['planner_distribution'])->map(fn ($data, $planner) => [
                        $planner,
                        $data['unit_count'] ?? 0,
                        number_format($data['linear_ft'] ?? 0, 2),
                        number_format($data['acres'] ?? 0, 4),
                    ])->toArray();
                    $this->table(['Planner', 'Units', 'Linear Ft', 'Acres'], $plannerRows);
                }
            }

            $this->newLine();

            return $this->outputData($units->toArray(), 'units');
        } catch (\Exception $e) {
            $this->error('Failed to fetch units: '.$e->getMessage());

            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    private function fetchRawUnits(string $workOrder, int $limit): int
    {
        $response = $this->makeRawRequest(
            config('workstudio.views.planned_units'),
            [
                'FilterName' => 'WO#',
                'FilterValue' => $workOrder,
                'FilterCaption' => 'WO Number',
            ]
        );

        if (! $response) {
            return Command::FAILURE;
        }

        $this->showRawResponse($response, $limit);

        return Command::SUCCESS;
    }

    private function fetchRawView(): int
    {
        $viewGuid = $this->ask('Enter view GUID');
        $filterName = $this->ask('Enter filter name (or press enter to skip)', '');
        $filterValue = $filterName ? $this->ask('Enter filter value') : '';

        $filter = [];
        if ($filterName) {
            $filter = [
                'FilterName' => $filterName,
                'FilterValue' => $filterValue,
                'FilterCaption' => $filterValue,
            ];
        }

        $response = $this->makeRawRequest($viewGuid, $filter);

        if (! $response) {
            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $this->showRawResponse($response, $limit);

        return Command::SUCCESS;
    }

    private function makeRawRequest(string $viewGuid, array $filter): ?array
    {
        $credentials = $this->credentialManager->getCredentials();
        $payload = [
            'Protocol' => 'GETVIEWDATA',
            'ViewDefinitionGuid' => $viewGuid,
            'ViewFilter' => $filter,
            'ResultFormat' => 'DDOTable',
        ];

        $this->info('Making request to WorkStudio API...');

        if ($this->output->isVerbose()) {
            $this->line('Payload: '.json_encode($payload, JSON_PRETTY_PRINT));
        }

        try {
            $url = rtrim(config('workstudio.base_url'), '/').'/'.($payload['Protocol'] ?? 'GETVIEWDATA');

            $response = Http::workstudio()
                ->withBasicAuth($credentials['username'], $credentials['password'])
                ->retry(3, 1000)
                ->post($url, $payload);

            if ($response->failed()) {
                $this->error("API returned status: {$response->status()}");
                $this->error($response->body());

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->error('Request failed: '.$e->getMessage());

            return null;
        }
    }

    private function showRawResponse(array $response, int $limit): void
    {
        $this->info('=== Raw API Response Structure ===');
        $this->table(['Key', 'Value'], [
            ['Protocol', $response['Protocol'] ?? 'N/A'],
            ['Has DataSet', isset($response['DataSet']) ? 'Yes' : 'No'],
        ]);

        // Show error details if ERROR protocol
        if (($response['Protocol'] ?? '') === 'ERROR') {
            $this->newLine();
            $this->error('=== API Error Details ===');
            $this->line(json_encode($response, JSON_PRETTY_PRINT));

            return;
        }

        if (isset($response['DataSet'])) {
            $headings = $response['DataSet']['Heading'] ?? [];
            $data = $response['DataSet']['Data'] ?? [];

            $this->newLine();
            $this->info('=== DataSet Headings ('.count($headings).' columns) ===');
            $this->line(implode(', ', $headings));

            $this->newLine();
            $this->info('=== DataSet Rows ('.count($data).' total) ===');

            if ($limit > 0) {
                $data = array_slice($data, 0, $limit);
                $this->comment("Showing first {$limit} rows");
            }

            // Transform to associative for better display
            $transformed = $this->ddoTransformer->transform($response);

            if ($limit > 0) {
                $transformed = $transformed->take($limit);
            }

            $this->outputData($transformed->toArray(), 'raw');
        }
    }

    private function outputData(array $data, string $type): int
    {
        $outputFile = $this->option('output');

        if ($this->option('json') || $outputFile) {
            $flags = $this->option('pretty') ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0;
            $json = json_encode([
                'type' => $type,
                'count' => count($data),
                'fetched_at' => now()->toIso8601String(),
                'data' => $data,
            ], $flags);

            if ($outputFile) {
                file_put_contents($outputFile, $json);
                $this->info("Data saved to: {$outputFile}");
                $this->info('File size: '.number_format(strlen($json)).' bytes');

                return Command::SUCCESS;
            }

            $this->line($json);

            return Command::SUCCESS;
        }

        // Table output for small datasets
        if (count($data) === 0) {
            $this->warn('No data returned');

            return Command::SUCCESS;
        }

        // For table display, pick key columns based on type
        $columns = $this->getDisplayColumns($type);
        $rows = collect($data)->map(function ($row) use ($columns) {
            $display = [];
            foreach ($columns as $col) {
                $value = $row[$col] ?? '';
                // Truncate long values
                if (is_string($value) && strlen($value) > 40) {
                    $value = substr($value, 0, 37).'...';
                }
                if (is_array($value)) {
                    $value = json_encode($value);
                    if (strlen($value) > 40) {
                        $value = substr($value, 0, 37).'...';
                    }
                }
                $display[$col] = $value;
            }

            return $display;
        })->toArray();

        $this->table($columns, $rows);

        $this->newLine();
        $this->comment('Tip: Use --json for full data or --output=file.json to save');

        return Command::SUCCESS;
    }

    private function getDisplayColumns(string $type): array
    {
        return match ($type) {
            'circuits' => ['work_order', 'extension', 'title', 'api_status', 'total_miles', 'miles_planned', 'percent_complete'],
            'units' => ['VEGUNIT_UNIT', 'VEGUNIT_FORESTER', 'VEGUNIT_PERMSTAT', 'JOBVEGETATIONUNITS_LENGTHWRK', 'JOBVEGETATIONUNITS_ACRES'],
            default => array_slice(array_keys($this->getFirstRow($type) ?? []), 0, 7),
        };
    }

    private function getFirstRow(string $type): ?array
    {
        return null; // Fallback
    }
}
