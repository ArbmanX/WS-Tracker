<?php

namespace App\Services\WorkStudio;

use Exception;
use App\ExecutionTimer;
use App\Services\WorkStudio\GetQuery\GetQueryExecutor;
use App\Services\WorkStudio\GetQuery\GetQueryResponseParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Services\WorkStudio\Queries\PlannerOwnedCircuitsQuery;
use App\Services\WorkStudio\Transformers\DailySpanTransformer;
use App\Services\WorkStudio\Queries\CircuitWithDailyRecordsQuery;
use App\Services\WorkStudio\Queries\VegPlanners\AssessmentMetrics;
use App\Services\WorkStudio\Queries\VegPlanners\VegAssessmentQueries;

class GetQueryService
{
    //  TODO: eventually will need to get signed in users credentials to use for parameters. 
    // credential manager is already pluged in 

    public string $sqlState = '';

    public function __construct(
        private GetQueryExecutor $executor,
        private GetQueryResponseParser $parser,
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
    public function executeQuery(string $sql, ?int $userId = null): array
    {
        $this->sqlState = $sql;

        return $this->executor->execute($sql, $userId);
    }

    /**
     * Execute a query and parse the response as a collection of associative arrays.
     * Used for standard DDOTable responses with Heading and Data arrays.
     */
    public function executeAndHandle(string $sql, ?int $userId = null): Collection
    {
        $response = $this->executeQuery($sql, $userId);

        return $this->parser->parse($response);
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
        return $this->parser->parseTabularResponse($response);
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
        return $this->parser->parseJsonResponse($response);
    }

    // TODO 
        // Exctact the code below to its own class, it only gets the structure sql statement as a string 


    /** Get job guids for assessments (used for requesting individual jobs).
     *
     * @param  string  $method  The method that chooses the disired response returned
     * @param  string|null  $username  The planner's username
     */
    public function getJobGuids(): Collection
    {
        $sql = VegAssessmentQueries::getAllJobGUIDsForEntireScopeYear();

        return $this->executeAndHandle($sql, null);
    }

    public function getSystemWideMetrics(): Collection
    {
        $sql = VegAssessmentQueries::systemWideDataQuery();

        return $this->executeAndHandle($sql, null);
    }

    public function getRegionalMetrics(): Collection
    {
        $sql = VegAssessmentQueries::groupedByRegionDataQuery();

        return $this->executeAndHandle($sql, null);
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

        Log::debug('WorkStudio queryAll debug summary', [
            'system_wide_count' => $systemWideDataQuery->count(),
            'grouped_region_count' => $groupedByRegionDataQuery->count(),
            'grouped_circuit_count' => $groupedByCircuitDataQuery->count(),
        ]);
        $timer->logTotalTime();

        return $groupedByCircuitDataQuery;
    }

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
    ): Collection {
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

        $first = $result->first();

        return is_array($first) ? $first : null;
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

}
