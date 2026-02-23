<?php

namespace App\Services\WorkStudio\GetQuery;

use App\Services\WorkStudio\ApiCredentialManager;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetQueryExecutor
{
    public function __construct(
        private ApiCredentialManager $credentialManager,
    ) {}

    /**
     * Execute a raw GETQUERY request and return the decoded API payload.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function execute(string $sql, ?int $userId = null): array
    {
        $credentials = $this->credentialManager->getCredentials($userId);
        $url = rtrim(config('workstudio.base_url'), '/').'/GETQUERY';

        $payload = [
            'Protocol' => 'GETQUERY',
            'DBParameters' => sprintf(
                "USER NAME=%s\r\nPASSWORD=%s\r\n",
                $credentials['username'],
                $credentials['password']
            ),
            'SQL' => $sql,
        ];

        try {
            $response = Http::workstudio()
                ->withBasicAuth($credentials['username'], $credentials['password'])
                ->withOptions(['on_stats' => function (\GuzzleHttp\TransferStats $stats) {
                    logger()->info('WorkStudio GETQUERY transfer time', [
                        'seconds' => $stats->getTransferTime(),
                    ]);
                }])
                ->post($url, $payload);

            $data = $response->json() ?? [];

            if ((isset($data['protocol']) && $data['protocol'] === 'ERROR') || isset($data['errorMessage'])) {
                Log::error('WorkStudio API returned GETQUERY error', [
                    'status_code' => $response->status(),
                    'error' => trim(($data['protocol'] ?? '').' '.($data['errorMessage'] ?? '')) ?: 'Unknown',
                    'sql' => substr($sql, 0, 500),
                ]);

                throw new Exception(
                    trim(($data['protocol'] ?? '').' '.($data['errorMessage'] ?? 'Unknown')) ?: 'Unknown API error',
                    500
                );
            }

            $response->throw();

            return $data;
        } catch (Exception $e) {
            Log::error('WorkStudio GETQUERY failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'sql' => substr($sql, 0, 500),
            ]);

            throw $e;
        }
    }
}
