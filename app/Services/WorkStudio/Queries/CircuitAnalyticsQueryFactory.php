<?php

namespace App\Services\WorkStudio\Queries;

use App\Models\Circuit;
use Illuminate\Database\Eloquent\Builder;

class CircuitAnalyticsQueryFactory
{
    /**
     * Base analytics query for active reporting circuits.
     */
    public function baseIncluded(): Builder
    {
        return Circuit::query()
            ->forAnalytics()
            ->whereNull('deleted_at')
            ->notExcluded();
    }

    /**
     * Analytics query filtered to circuits assigned to specific planners.
     *
     * @param  array<int>  $plannerIds
     */
    public function forPlannerIds(array $plannerIds, ?int $regionId = null): Builder
    {
        return $this->baseIncluded()
            ->whereHas('planners', fn ($q) => $q->whereIn('users.id', $plannerIds))
            ->when($regionId, fn ($q) => $q->where('region_id', $regionId));
    }
}
