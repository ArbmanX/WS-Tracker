<?php

namespace App\Services\WorkStudio;

use Exception;
use App\ExecutionTimer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use App\Services\WorkStudio\Queries\PlannerOwnedCircuitsQuery;
use App\Services\WorkStudio\Transformers\DailySpanTransformer;
use App\Services\WorkStudio\Queries\CircuitWithDailyRecordsQuery;
use App\Services\WorkStudio\Queries\VegPlanners\AssessmentMetrics;
use App\Services\WorkStudio\Queries\VegPlanners\VegAssessmentQueries;

class GetQueryService
{
    //  TODO: eventually will need to get signed in users credentials to use for parameters. 
    // credential manager is already pluged in 

    public $sqlState;

    public function __construct(
        private ?ApiCredentialManager $credentialManager = null,
    ) {}

    /**
     * Execute a raw SQL query against the WorkStudio API.
     *
     * @param  string  $sql  The SQL query to execute
     * @param  int|null  $userId  Optional user ID for credentials
     * @return array The raw response data
     *
     * @throws Exception
     */
    public function executeQuery(string $sql, ?int $userId = null, bool $getStations = false): ?array
    {
        $credentials = $this->getCredentials($userId);

        $payload = [
            'Protocol' => 'GETQUERY',
            'DBParameters' => "USER NAME=ASPLUNDH\\cnewcombe\r\nPASSWORD=chrism\r\n",
            'SQL' => $sql,
        ];

        $url = rtrim(config('workstudio.base_url'), '/') . '/GETQUERY';
        $this->sqlState = $sql;

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth('ASPLUNDH\cnewcombe', 'chrism')
                ->timeout(120)
                ->connectTimeout(30)
                ->withOptions(['on_stats' => function (\GuzzleHttp\TransferStats $stats) {
                    $transferTime = $stats->getTransferTime(); // seconds
                    logger()->info("Transfer time: {$transferTime}s");
                }])
                ->post($url, $payload);

            $data = $response->json();

            if (isset($data['protocol']) && $data['protocol'] == 'ERROR' || isset($data['errorMessage'])) {
                Log::error('WorkStudio API returned error', [
                    'Status_Code' => 500,
                    'error' => $data['protocol'] . ' ' . $data['errorMessage'] ?? 'Unknown',
                    'sql' => substr($sql, 0, 500),

                ]);


                throw new Exception(json_encode(
                    [
                        'Status_Code' => $response->status(),
                        'Message' => $data['protocol'] . ' in the ' . class_basename($this) . ' ' . $data['errorMessage'],
                        'SQL' => json_encode($sql, JSON_PRETTY_PRINT),
                    ]
                ) ?? 'Unknown API error', 500);
            }

            $response->throw();

            return $data;
        } catch (Exception $e) {
            Log::error('WorkStudio GETQUERY failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'sql' => substr($sql, 0, 500), // Log first 500 chars of SQL
            ]);

            throw $e;
        }
    }

    /**
     * Execute a query and parse the response as a collection of associative arrays.
     * Used for standard DDOTable responses with Heading and Data arrays.
     */
    public function executeAndHandle(string $sql, ?int $userId = null): Collection|array
    {
        $response = $this->executeQuery($sql, $userId);

        if (isset($response['Heading']) && str_contains($response['Heading'][0], 'JSON_')) {
            return $this->transformJsonResponse($response);
        }

        if (isset($response['Heading']) && count($response) > 1) {
            return $this->transformArrayResponse($response);
        }

        return collect([]);
    }

    /**
     * Execute a query and parse the response as a collection of associative arrays.
     * Used for standard DDOTable responses with Heading and Data arrays.
     *
     * @param  string  $sql  The SQL query to execute
     * @param  int|null  $userId  Optional user ID for credentials
     */
    public function transformArrayResponse(array $response): Collection
    {
        if (! isset($response['Data']) || ! isset($response['Heading'])) {
            return collect([]);
        }

        $prepared = collect($response['Data'])->map(function ($row) use ($response) {
            return array_combine($response['Heading'], $row);
        });

        return $prepared;
    }

    /**
     * Execute a query that returns FOR JSON PATH formatted data.
     * Parses the chunked JSON response into a PHP array.
     *
     * @param  string  $sql  The SQL query (should end with FOR JSON PATH)
     * @param  int|null  $userId  Optional user ID for credentials
     */
    public function transformJsonResponse(array $response): Collection
    {

        if (! isset($response['Data']) || empty($response['Data'])) {
            return collect([]);
        }

        // FOR JSON PATH responses come back as chunked strings in Data array
        // Each row contains a single element which is a JSON string fragment
        $jsonString = implode('', array_map(fn($row) => $row[0], $response['Data']));

        // Remove control characters that might break JSON parsing
        $jsonString = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $jsonString);

        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse JSON response from WorkStudio', [
                'error' => json_last_error_msg(),
                'raw_length' => strlen($jsonString),
            ]);

            return collect([]);
        }

        return collect([$data]) ?? [];
    }


    public function queryAll(): Collection
    {
        $timer = new ExecutionTimer();
        $timer->startTotal();

        $timer->start('systemWideDataQuery');
        $sql = VegAssessmentQueries::systemWideDataQuery();
        $systemWideDataQuery = $this->executeAndHandle($sql, null);
        $timer->stop('systemWideDataQuery');

        $timer->start('groupedByRegionDataQuery');
        $sql = VegAssessmentQueries::groupedByRegionDataQuery();
        $groupedByRegionDataQuery = $this->executeAndHandle($sql, null);
        $timer->stop('groupedByRegionDataQuery');
        
        

        $timer->start('groupedByCircuitDataQuery');
        $sql = VegAssessmentQueries::groupedByCircuitDataQuery();
        $groupedByCircuitDataQuery = $this->executeAndHandle($sql, null);
        $timer->stop('groupedByCircuitDataQuery');

        dump('$systemWideDataQuery',$systemWideDataQuery);
        dump('$groupedByRegionDataQuery',$groupedByRegionDataQuery);
        dump('$groupedByCircuitDataQuery',$groupedByCircuitDataQuery);
        $timer->logTotalTime();
        return collect($groupedByCircuitDataQuery);
    }

    public function test()
    {

        $sql = VegAssessmentQueries::test();


        return $this->executeAndHandle($sql, null);
    }

    // TODO need to add the following params for a more dynamic selection
    // @param  int|null  $userId  Optional user ID for credentials
    // @param  string  $contractor  The contractor name

    /**
     * Get planner-owned assessments (basic info without daily records).
     *
     * @param  string  $method  The method that chooses the disired response returned
     * @param  string|null  $username  The planner's username
     */
    public function getAssessmentsBaseData(string $method, ?string $username = null): Collection
    {

        switch ($method) {
            case 'full_scope':
                $sql = AssessmentMetrics::getBaseDataForEntireScopeYearAssessments();
                break;

            case 'active_owned':
                $sql = AssessmentMetrics::getBaseDataForActiveAndOwnedAssessments();
                break;

            case 'username':
                $sql = AssessmentMetrics::getBaseDataForActiveAssessmentsByUsername($username);
                break;

            default:
                return collect([]);
                break;
        }
        return $this->executeAndHandle($sql, null);
    }

    /** Get job guids for assessments (used for requesting individual jobs).
     *
     * @param  string  $method  The method that chooses the disired response returned
     * @param  string|null  $username  The planner's username
     */
    public function getJobGuids(string $method, ?string $username = null): Collection
    {
        switch ($method) {
            case 'full_scope':
                return $this->executeAndHandle(
                    AssessmentMetrics::getAllJobGUIDsForEntireScopeYear(),
                    null
                );
                break;

            case 'active_owned':
                return $this->executeAndHandle(
                    AssessmentMetrics::getAllJobGUIDsForActiveAndOwnedAssessments(),
                    null
                );
                break;

            case 'username':
                return $this->executeAndHandle(
                    AssessmentMetrics::getAllJobGUIDsForActiveAssessmentsByUsername(
                        $username
                    ),
                    null
                );
                break;

            default:
                return collect([]);
                break;
        }


        return $this->executeAndHandle($sql, null);
    }

    public function getAssessmentUnits(string $method, ?string $username = null, ?string $jobguid = null): Collection
    {
        switch ($method) {
            case 'job_guid':
                $sql = AssessmentMetrics::getAllUnitsForAssessmentByJobGUID($jobguid);
                break;

            case 'active_owned':
                $sql = AssessmentMetrics::getAllUnitsForActiveAndOwnedAssessments();
                break;

            case 'username':
                $sql = AssessmentMetrics::getAllUnitsForActiveAssessmentsByUsername($username);
                break;

            default:
                return collect([]);
                break;
        }

        return $this->executeAndHandle($sql, null);
    }


    /**
     * Get planner-owned circuits (basic info without daily records).
     *
     * @param  string  $username  The planner's username
     * 
     * @param  int|null  $userId  Optional user ID for credentials
     */
    public function getPlannerOwnedCircuits(
        string $username,
        string $contractor = 'Asplundh',
        ?int $userId = null
    ): Collection {
        $sql = PlannerOwnedCircuitsQuery::getOwnedAndActiveCircuits($username, $contractor);

        return $this->executeAndHandle($sql, $userId);
    }

    // TODO 
    /**
     * Get an assessment or assessments JobGUID for a single planner
     *  (This should be stored to the data base and used to keep track of.
     *  snapshots, hashes, user defined edits, etc will be associated with the Job GUID.
     *  SO the Job Guid will get/tell you everything about this job until it is closed out. ).
     *
     * @param  string  $username  The planner's username
     * @param  string  $contractor  The contractor name
     * @param  int|null  $userId  Optional user ID for credentials
     */
    public function getJobGuidsPerPlannerUsername(
        ?string $username = null,
        string $contractor = 'Asplundh',
        ?int $userId = null
    ): Collection {

        $sql = '';

        if ($username === null) {
            return $this->executeAndHandle(
                PlannerOwnedCircuitsQuery::getAllJobGUIDsForActiveAndOwnedAssessments()
            );
        }

        return $this->executeAndHandle(
            PlannerOwnedCircuitsQuery::getOwnedAndActiveJobGuidByPlannerUsername(
                $username,
                $contractor
            )
        );
    }

    /**
     * Get planner circuits with unit counts and daily records.
     *
     * @param  string  $username  The planner's username
     * @param  string  $contractor  The contractor name
     * @param  int|null  $userId  Optional user ID for credentials
     */
    public function getPlannerCircuitsWithDailyRecords(
        string $username,
        string $contractor = 'Asplundh',
        ?int $userId = null
    ): array {
        $sql = CircuitWithDailyRecordsQuery::getForPlannerMonitoring($username, $contractor);

        return $this->executeAndHandle($sql, $userId);
    }

    /**
     * Get a single circuit by JOBGUID with unit counts and daily records.
     *
     * @param  string  $jobGuid  The circuit's JOBGUID
     * @param  int|null  $userId  Optional user ID for credentials
     */
    public function getCircuitDetails(string $jobGuid, ?int $userId = null): ?array
    {
        $sql = CircuitWithDailyRecordsQuery::getByJobGuid($jobGuid);
        $result = $this->executeAndHandle($sql, $userId);

        // Single circuit query returns object (WITHOUT_ARRAY_WRAPPER), not array
        // But executeJsonQuery wraps it, so we might get the object directly
        return is_array($result) && ! isset($result[0]) ? $result : ($result[0] ?? null);
    }

    /**
     * Get raw station/unit data for a circuit.
     *
     * @param  string  $jobGuid  The circuit's JOBGUID
     */
    public function getAllUnitsByJobGuid(string $jobGuid): Collection
    {
        $sql = PlannerOwnedCircuitsQuery::getAllUnits($jobGuid);

        return $this->executeAndHandle($sql, null);
    }

    /**
     * Get daily span summaries for a circuit, grouped by assessed date.
     *
     * @param  string  $jobGuid  The circuit's JOBGUID
     * @return array Transformed data with daily groupings and totals
     */
    public function getDailySpanSummary(string $jobGuid): array
    {
        $rawData = $this->getAllUnitsByJobGuid($jobGuid);

        $transformer = new DailySpanTransformer;

        return $transformer->transform($rawData);
    }

    /**
     * Get credentials for API requests.
     */
    private function getCredentials(?int $userId = null): array
    {
        if ($this->credentialManager) {
            return $this->credentialManager->getCredentials($userId);
        }

        // Fallback to config if no credential manager
        return [
            'username' => config('workstudio.service_account.username'),
            'password' => config('workstudio.service_account.password'),
        ];
    }
}
