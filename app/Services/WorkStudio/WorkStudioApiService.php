<?php

namespace App\Services\WorkStudio;

use App\Services\WorkStudio\Contracts\WorkStudioApiInterface;
use App\Services\WorkStudio\Transformers\CircuitTransformer;
use App\Services\WorkStudio\Transformers\DDOTableTransformer;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkStudioApiService implements WorkStudioApiInterface
{
    public string $circuitQueryStatement =
        "SELECT
            WSREQSS.JOBGUID AS Job_ID,
            VEGJOB.LINENAME AS Line_Name,
            WSREQSS.WO AS Work_Order,
            WSREQSS.EXT AS Extension,
            WSREQSS.STATUS AS Status,
            WSREQSS.TAKEN AS Taken,

            YEAR(
                REPLACE(
                    REPLACE(
                        WPStartDate_Assessment_Xrefs.WP_STARTDATE, '/Date(', ''),
                 ')/', '')
            ) AS Scope_Year,

            VEGJOB.OPCO AS Utility,
            VEGJOB.REGION AS Region,
            VEGJOB.SERVCOMP AS Department,
            WSREQSS.JOBTYPE AS Job_Type,
            VEGJOB.CYCLETYPE AS Cycle_Type,

            VEGJOB.LENGTH AS Total_Miles,
            VEGJOB.LENGTHCOMP AS Completed_Miles,
            VEGJOB.PRCENT AS Percent_Complete,

            VEGJOB.CONTRACTOR AS Contractor,
            WSREQSS.TAKENBY AS Current_Owner,
            SS.MODIFIEDBY AS Last_Modified_By,
            FORMAT(
                CAST(
                    CAST(SS.EDITDATE AS DATETIME)
                    AT TIME ZONE 'UTC'
                    AT TIME ZONE 'Eastern Standard Time'
                AS DATETIME),
                    'MM/dd/yyyy h:mm tt'
            ) AS Last_Modified_On,

            WSREQSS.ASSIGNEDTO AS Assigned_To,
            VEGJOB.COSTMETHOD AS Cost_Method,
            VEGJOB.CIRCCOMNTS AS Circuit_Comments

            FROM SS
            INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
            INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
            LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
            WHERE VEGJOB.REGION IN (
                SELECT RESOURCEPERSON.GROUPDESC
                FROM RESOURCEPERSON
                WHERE RESOURCEPERSON.GROUPTYPE = 'GROUP'
                AND RESOURCEPERSON.USERNAME = 'ASPLUNDH\\cnewcombe'
            )
            AND WSREQSS.STATUS = 'ACTIV'
            AND VEGJOB.CONTRACTOR IN ('Asplundh', 'PPL')
            AND WSREQSS.JOBTYPE IN ('Assessment', 'Assessment Dx', 'Split_Assessment', 'Tandem_Assessment')
        ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";

    private ?int $currentUserId = null;

    public function __construct(
        private ?ApiCredentialManager $credentialManager,
        private ?DDOTableTransformer $ddoTransformer,
        private ?CircuitTransformer $circuitTransformer,
    ) {}

    /**
     * Check if the WorkStudio API is reachable.
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::workstudio()->get($this->getBaseUrlWithoutPath());

            return ! $response->serverError();
        } catch (ConnectionException $e) {
            Log::warning('WorkStudio API health check failed', [
                'url' => $this->getBaseUrlWithoutPath(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get view data from WorkStudio using a view GUID and filter.
     */
    public function getViewData(string $viewGuid, array $filter, ?int $userId = null): array
    {
        $this->currentUserId = $userId;
        $credentials = $this->credentialManager->getCredentials($userId);

        $payload = [
            'Protocol' => 'GETVIEWDATA',
            'ViewDefinitionGuid' => $viewGuid,
            'ViewFilter' => array_merge([
                'PersistFilter' => true,
                'ClassName' => 'TViewFilter',
            ], $filter),
            'ResultFormat' => 'DDOTable',
        ];

        return $this->makeRequest($payload, $credentials, $userId);
    }

    /**
     * Get circuits (vegetation assessments) filtered by status.
     */
    public function getCircuitsByStatus(string $status, ?int $userId = null): Collection
    {
        $response = $this->getViewData(
            config('workstudio.views.vegetation_assessments'),
            [
                'FilterName' => 'By Job Status',
                'FilterValue' => $status,
                'FilterCaption' => $this->getStatusCaption($status),
            ],
            $userId
        );

        // Transform DDOTable to collection of arrays
        $rawData = $this->ddoTransformer->transform($response);

        // Transform to circuit data
        return $this->circuitTransformer->transformCollection($rawData);
    }

    /**
     * Get planned units for a specific work order.
     */
    public function getPlannedUnits(string $workOrder, ?int $userId = null): Collection
    {
        $response = $this->getViewData(
            config('workstudio.views.planned_units'),
            [
                'FilterName' => 'WO#',
                'FilterValue' => $workOrder,
                'FilterCaption' => 'WO Number',
            ],
            $userId
        );

        // Return raw DDOTable data as collection for aggregate processing
        return $this->ddoTransformer->transform($response);
    }

    /**
     * Get the current API credentials info (without exposing password).
     */
    public function getCurrentCredentialsInfo(): array
    {
        return $this->credentialManager->getCredentialsInfo($this->currentUserId);
    }

    /**
     * Make the actual HTTP request to WorkStudio with retry logic.
     */
    private function makeRequest(array $payload, array $credentials, ?int $userId): array
    {
        $url = rtrim(config('workstudio.base_url'), '/').'/'.($payload['Protocol'] ?? 'GETVIEWDATA');
        $maxRetries = config('workstudio.max_retries', 5);

        $response = Http::workstudio()
            ->withBasicAuth($credentials['username'], $credentials['password'])
            ->retry(
                $maxRetries,
                function (int $attempt, Exception $exception) use ($url) {
                    // Exponential backoff: 1s, 2s, 4s, 8s... capped at 30s
                    $delay = min(1000 * pow(2, $attempt - 1), 30000);

                    Log::warning('WorkStudio API request failed, retrying', [
                        'url' => $url,
                        'attempt' => $attempt,
                        'max_retries' => config('workstudio.max_retries', 5),
                        'delay_ms' => $delay,
                        'error' => $exception->getMessage(),
                        'exception_class' => get_class($exception),
                    ]);

                    return $delay;
                },
                function (Exception $exception, PendingRequest $request) use ($userId) {
                    // Don't retry 401 authentication errors
                    if ($exception instanceof RequestException && $exception->response?->status() === 401) {
                        $this->credentialManager->markFailed($userId);

                        Log::error('WorkStudio API authentication failed', [
                            'user_id' => $userId,
                        ]);

                        return false;
                    }

                    return true;
                }
            )
            ->post($url, $payload);

        $response->throw();

        $this->credentialManager->markSuccess($userId);

        $data = $response->json();

        if (! isset($data['Protocol']) || $data['Protocol'] !== 'DATASET') {
            Log::warning('Unexpected WorkStudio API response format', [
                'protocol' => $data['Protocol'] ?? 'missing',
            ]);
        }

        return $data;
    }

    /**
     * Get the base URL without the protocol path.
     */
    private function getBaseUrlWithoutPath(): string
    {
        $baseUrl = config('workstudio.base_url');

        return preg_replace('#/DDOProtocol/?$#', '', $baseUrl);
    }

    /**
     * Get the display caption for a status code.
     */
    private function getStatusCaption(string $status): string
    {
        $statuses = config('workstudio.statuses', []);

        foreach ($statuses as $config) {
            if (($config['value'] ?? '') === $status) {
                return $config['caption'] ?? $status;
            }
        }

        return $status;
    }
}
