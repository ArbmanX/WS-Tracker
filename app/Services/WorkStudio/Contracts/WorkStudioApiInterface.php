<?php

namespace App\Services\WorkStudio\Contracts;

use Illuminate\Support\Collection;

interface WorkStudioApiInterface
{
    /**
     * Check if the WorkStudio API is reachable.
     */
    public function healthCheck(): bool;

    /**
     * Get view data from WorkStudio using a view GUID and filter.
     *
     * @param  string  $viewGuid  The view definition GUID
     * @param  array  $filter  Filter parameters (FilterName, FilterValue, etc.)
     * @param  int|null  $userId  Optional user ID for credential selection
     * @return array Raw API response
     */
    public function getViewData(string $viewGuid, array $filter, ?int $userId = null): array;

    /**
     * Get circuits (vegetation assessments) filtered by status.
     *
     * @param  string  $status  Status filter value (ACTIV, QC, REWRK, CLOSE)
     * @param  int|null  $userId  Optional user ID for credential selection
     */
    public function getCircuitsByStatus(string $status, ?int $userId = null): Collection;

    /**
     * Get planned units for a specific work order.
     *
     * @param  string  $workOrder  The work order number (e.g., "2025-1234")
     * @param  int|null  $userId  Optional user ID for credential selection
     */
    public function getPlannedUnits(string $workOrder, ?int $userId = null): Collection;

    /**
     * Get the current API credentials info (without exposing password).
     *
     * @return array{type: string, username: string, user_id: int|null}
     */
    public function getCurrentCredentialsInfo(): array;
}
