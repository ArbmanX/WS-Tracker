<?php

namespace App\Services\WorkStudio\Queries;

use App\Models\Circuit;
use App\Support\WorkStudioStatus;
use Illuminate\Support\Collection;

class CircuitFilterOptionsService
{
    public function __construct(
        private CircuitAnalyticsQueryFactory $analyticsQueryFactory,
    ) {}

    /**
     * Status options keyed by code with display labels.
     *
     * @return array<string, string>
     */
    public function apiStatusOptions(): array
    {
        $availableCodes = Circuit::query()
            ->select('api_status')
            ->distinct()
            ->whereNotNull('api_status')
            ->orderBy('api_status')
            ->pluck('api_status')
            ->toArray();

        $canonical = collect(WorkStudioStatus::all())
            ->filter(fn (string $code) => in_array($code, $availableCodes, true))
            ->mapWithKeys(fn (string $code) => [$code => WorkStudioStatus::labelFor($code)]);

        $additional = collect($availableCodes)
            ->reject(fn (string $code) => in_array($code, WorkStudioStatus::all(), true))
            ->mapWithKeys(fn (string $code) => [$code => $code]);

        return $canonical->merge($additional)->toArray();
    }

    /**
     * Scope year options keyed by year.
     *
     * @return array<string, string>
     */
    public function scopeYearOptions(): array
    {
        return Circuit::query()
            ->selectRaw('DISTINCT SUBSTR(work_order, 1, 4) as scope_year')
            ->whereRaw("SUBSTR(work_order, 1, 4) BETWEEN '2000' AND '2099'")
            ->orderByDesc('scope_year')
            ->toBase()
            ->pluck('scope_year', 'scope_year')
            ->toArray();
    }

    /**
     * Cycle type options keyed by cycle type from all circuits.
     *
     * @return array<string, string>
     */
    public function cycleTypeOptions(): array
    {
        return Circuit::query()
            ->select('cycle_type')
            ->distinct()
            ->whereNotNull('cycle_type')
            ->where('cycle_type', '!=', '')
            ->orderBy('cycle_type')
            ->pluck('cycle_type', 'cycle_type')
            ->toArray();
    }

    /**
     * Cycle types for analytics-included circuits (used by assessment filters).
     *
     * @return Collection<int, string>
     */
    public function analyticsCycleTypes(): Collection
    {
        return $this->analyticsQueryFactory
            ->baseIncluded()
            ->whereNotNull('cycle_type')
            ->where('cycle_type', '!=', '')
            ->distinct()
            ->orderBy('cycle_type')
            ->pluck('cycle_type');
    }

    /**
     * Planner "taken_by" options from all circuits.
     *
     * @return array<string, string>
     */
    public function plannerTakenByOptions(): array
    {
        return Circuit::query()
            ->select('taken_by')
            ->distinct()
            ->whereNotNull('taken_by')
            ->where('taken_by', '!=', '')
            ->orderBy('taken_by')
            ->pluck('taken_by', 'taken_by')
            ->toArray();
    }

    /**
     * Planner "taken_by" list for analytics-included circuits.
     *
     * @return Collection<int, string>
     */
    public function analyticsPlannerTakenBy(): Collection
    {
        return $this->analyticsQueryFactory
            ->baseIncluded()
            ->whereNotNull('taken_by')
            ->where('taken_by', '!=', '')
            ->distinct()
            ->orderBy('taken_by')
            ->pluck('taken_by');
    }
}
