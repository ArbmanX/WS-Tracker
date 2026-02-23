<?php

namespace App\Services\WorkStudio\GetQuery;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GetQueryResponseParser
{
    /**
     * Parse a GETQUERY payload into a normalized collection.
     *
     * @param  array<string, mixed>  $response
     */
    public function parse(array $response): Collection
    {
        if (isset($response['Heading'][0]) && str_contains((string) $response['Heading'][0], 'JSON_')) {
            return $this->parseJsonResponse($response);
        }

        if (isset($response['Heading']) && count($response) > 1) {
            return $this->parseTabularResponse($response);
        }

        return collect();
    }

    /**
     * Parse standard DDOTable response with Heading/Data arrays.
     *
     * @param  array<string, mixed>  $response
     */
    public function parseTabularResponse(array $response): Collection
    {
        if (! isset($response['Data'], $response['Heading']) || ! is_array($response['Data']) || ! is_array($response['Heading'])) {
            return collect();
        }

        return collect($response['Data'])
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => array_combine($response['Heading'], $row) ?: []);
    }

    /**
     * Parse chunked FOR JSON PATH responses.
     *
     * @param  array<string, mixed>  $response
     */
    public function parseJsonResponse(array $response): Collection
    {
        if (! isset($response['Data']) || ! is_array($response['Data']) || empty($response['Data'])) {
            return collect();
        }

        $jsonString = implode('', array_map(
            fn ($row) => is_array($row) && isset($row[0]) ? (string) $row[0] : '',
            $response['Data']
        ));

        $jsonString = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $jsonString) ?? '';
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse WorkStudio JSON payload', [
                'error' => json_last_error_msg(),
                'raw_length' => strlen($jsonString),
            ]);

            return collect();
        }

        return collect([$data]);
    }
}
