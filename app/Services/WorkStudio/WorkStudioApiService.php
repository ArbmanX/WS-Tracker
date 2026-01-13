<?php

namespace App\Services\WorkStudio;

use App\Services\WorkStudio\Contracts\WorkStudioApiInterface;
use App\Services\WorkStudio\Transformers\CircuitTransformer;
use App\Services\WorkStudio\Transformers\DDOTableTransformer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkStudioApiService implements WorkStudioApiInterface
{
    private ?int $currentUserId = null;

    public function __construct(
        private ApiCredentialManager $credentialManager,
        private DDOTableTransformer $ddoTransformer,
        private CircuitTransformer $circuitTransformer,
    ) {}

    /**
     * Check if the WorkStudio API is reachable.
     */
    public function healthCheck(): bool
    {
        try {
            /** @var Response $response */
            $response = Http::timeout(10)
                ->withOptions(['verify' => false]) // Handle self-signed certs
                ->get($this->getBaseUrlWithoutPath());

            return $response->status() < 500;
        } catch (ConnectionException $e) {
            Log::warning('WorkStudio API health check failed', [
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

        return $this->executeWithRetry(
            fn () => $this->makeRequest($payload, $credentials),
            $credentials['user_id']
        );
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
     * Execute a request with retry logic and exponential backoff.
     */
    private function executeWithRetry(callable $request, ?int $userId): array
    {
        $maxRetries = config('workstudio.max_retries', 5);
        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxRetries) {
            try {
                $response = $request();

                // Mark successful authentication
                $this->credentialManager->markSuccess($userId);

                return $response;

            } catch (RequestException $e) {
                $lastException = $e;

                // Handle authentication failures
                if ($e->response && $e->response->status() === 401) {
                    $this->credentialManager->markFailed($userId);

                    Log::error('WorkStudio API authentication failed', [
                        'user_id' => $userId,
                        'attempt' => $attempts + 1,
                    ]);

                    // Don't retry auth failures
                    throw new \RuntimeException('WorkStudio authentication failed', 401, $e);
                }

                $attempts++;
                $this->handleRetryDelay($attempts);

            } catch (ConnectionException $e) {
                $lastException = $e;
                $attempts++;

                Log::warning('WorkStudio API connection failed', [
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                ]);

                $this->handleRetryDelay($attempts);
            }
        }

        Log::error('WorkStudio API request failed after all retries', [
            'max_retries' => $maxRetries,
            'last_error' => $lastException?->getMessage(),
        ]);

        throw new \RuntimeException(
            'WorkStudio API request failed after '.$maxRetries.' attempts',
            0,
            $lastException
        );
    }

    /**
     * Make the actual HTTP request to WorkStudio.
     */
    private function makeRequest(array $payload, array $credentials): array
    {
        // WorkStudio API requires the protocol in the URL path
        $url = rtrim(config('workstudio.base_url'), '/').'/'.($payload['Protocol'] ?? 'GETVIEWDATA');

        /** @var Response $response */
        $response = Http::timeout(config('workstudio.timeout', 60))
            ->withOptions(['verify' => false]) // Handle self-signed certs
            ->withBasicAuth($credentials['username'], $credentials['password'])
            ->post($url, $payload);

        $response->throw();

        $data = $response->json();

        // Validate response format
        if (! isset($data['Protocol']) || $data['Protocol'] !== 'DATASET') {
            Log::warning('Unexpected WorkStudio API response format', [
                'protocol' => $data['Protocol'] ?? 'missing',
            ]);
        }

        return $data;
    }

    /**
     * Handle delay between retry attempts with exponential backoff.
     */
    private function handleRetryDelay(int $attempt): void
    {
        $baseDelay = 1000000; // 1 second in microseconds
        $delay = $baseDelay * pow(2, $attempt - 1);

        // Cap at 30 seconds
        $delay = min($delay, 30000000);

        usleep($delay);
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
