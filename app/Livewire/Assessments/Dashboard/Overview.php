<?php

namespace App\Livewire\Assessments\Dashboard;

use App\Livewire\Concerns\WithCircuitFilters;
use App\Models\Circuit;
use App\Models\Region;
use App\Models\RegionalWeeklyAggregate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Overview'])]
class Overview extends Component
{
    use WithCircuitFilters;

    #[Url]
    public string $viewMode = 'cards';

    public bool $panelOpen = false;

    public ?int $selectedRegionId = null;

    #[Url]
    public string $sortBy = 'name';

    #[Url]
    public string $sortDir = 'asc';

    #[Computed]
    public function regions(): Collection
    {
        $query = Region::query()
            ->where('is_active', true);

        if ($this->sortBy === 'name') {
            $query->orderBy('name', $this->sortDir);
        } else {
            $query->orderBy('sort_order');
        }

        return $query->get();
    }

    #[Computed]
    public function regionStats(): Collection
    {
        // When circuit filters are active, always compute live
        if ($this->hasActiveCircuitFilters()) {
            return $this->computeStatsFromCircuits();
        }

        // Try to get from weekly aggregates first
        $latestWeek = RegionalWeeklyAggregate::max('week_ending');

        if ($latestWeek) {
            return RegionalWeeklyAggregate::query()
                ->where('week_ending', $latestWeek)
                ->get()
                ->keyBy('region_id');
        }

        // Fallback: compute live from circuits table
        return $this->computeStatsFromCircuits();
    }

    /**
     * Compute regional stats directly from circuits when weekly aggregates are empty.
     * Returns a collection keyed by region_id with objects matching RegionalWeeklyAggregate shape.
     */
    protected function computeStatsFromCircuits(): Collection
    {
        $baseQuery = Circuit::query()
            ->whereNull('deleted_at')
            ->notExcluded();

        // Apply circuit filters
        $this->applyCircuitFilters($baseQuery);

        $stats = (clone $baseQuery)
            ->select([
                'region_id',
                DB::raw('COUNT(*) as total_circuits'),
                DB::raw("COUNT(CASE WHEN api_status = 'ACTIV' THEN 1 END) as active_circuits"),
                DB::raw("COUNT(CASE WHEN api_status = 'QC' THEN 1 END) as qc_circuits"),
                DB::raw("COUNT(CASE WHEN api_status = 'CLOSE' THEN 1 END) as closed_circuits"),
                DB::raw("COUNT(CASE WHEN api_status = 'REWRK' THEN 1 END) as rework_circuits"),
                DB::raw('COALESCE(SUM(total_miles), 0) as total_miles'),
                DB::raw('COALESCE(SUM(miles_planned), 0) as miles_planned'),
                DB::raw('COALESCE(SUM(total_miles) - SUM(miles_planned), 0) as miles_remaining'),
                DB::raw('COALESCE(AVG(percent_complete), 0) as avg_percent_complete'),
                DB::raw('COALESCE(SUM(total_acres), 0) as total_acres'),
            ])
            ->groupBy('region_id')
            ->get();

        // Get planner counts per region (with filters applied)
        $plannerQuery = DB::table('circuit_user')
            ->join('circuits', 'circuit_user.circuit_id', '=', 'circuits.id')
            ->whereNull('circuits.deleted_at')
            ->where('circuits.is_excluded', false);

        // Apply status filter to planner counts
        if (! empty($this->statusFilter)) {
            $plannerQuery->whereIn('circuits.api_status', $this->statusFilter);
        }

        // Apply cycle type filter to planner counts
        if (! empty($this->cycleTypeFilter)) {
            $plannerQuery->whereIn('circuits.cycle_type', $this->cycleTypeFilter);
        }

        $plannerCounts = $plannerQuery
            ->select('circuits.region_id', DB::raw('COUNT(DISTINCT circuit_user.user_id) as active_planners'))
            ->groupBy('circuits.region_id')
            ->pluck('active_planners', 'region_id');

        // Get unit stats from circuit_aggregates (with filters applied)
        $unitQuery = DB::table('circuit_aggregates')
            ->join('circuits', 'circuit_aggregates.circuit_id', '=', 'circuits.id')
            ->whereNull('circuits.deleted_at')
            ->where('circuits.is_excluded', false)
            ->where('circuit_aggregates.is_rollup', false)
            ->whereIn('circuit_aggregates.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('circuit_aggregates')
                    ->where('is_rollup', false)
                    ->groupBy('circuit_id');
            });

        // Apply status filter to unit stats
        if (! empty($this->statusFilter)) {
            $unitQuery->whereIn('circuits.api_status', $this->statusFilter);
        }

        // Apply cycle type filter to unit stats
        if (! empty($this->cycleTypeFilter)) {
            $unitQuery->whereIn('circuits.cycle_type', $this->cycleTypeFilter);
        }

        $unitStats = $unitQuery
            ->select(
                'circuits.region_id',
                DB::raw('COALESCE(SUM(circuit_aggregates.total_units), 0) as total_units'),
                DB::raw('COALESCE(SUM(circuit_aggregates.units_approved), 0) as units_approved'),
                DB::raw('COALESCE(SUM(circuit_aggregates.units_refused), 0) as units_refused'),
                DB::raw('COALESCE(SUM(circuit_aggregates.units_pending), 0) as units_pending'),
                DB::raw('COALESCE(SUM(circuit_aggregates.total_trees), 0) as total_trees'),
                DB::raw('COALESCE(SUM(circuit_aggregates.total_linear_ft), 0) as total_linear_ft')
            )
            ->groupBy('circuits.region_id')
            ->get()
            ->keyBy('region_id');

        // Transform to match RegionalWeeklyAggregate shape
        return $stats->map(function ($row) use ($plannerCounts, $unitStats) {
            $regionUnits = $unitStats[$row->region_id] ?? null;

            return (object) [
                'region_id' => $row->region_id,
                'week_ending' => now()->endOfWeek()->toDateString(),
                'week_starting' => now()->startOfWeek()->toDateString(),
                'active_circuits' => (int) $row->active_circuits,
                'qc_circuits' => (int) $row->qc_circuits,
                'closed_circuits' => (int) $row->closed_circuits,
                'total_circuits' => (int) $row->total_circuits,
                'excluded_circuits' => 0, // Not tracked in live computation
                'total_miles' => (float) $row->total_miles,
                'miles_planned' => (float) $row->miles_planned,
                'miles_remaining' => (float) $row->miles_remaining,
                'avg_percent_complete' => round((float) $row->avg_percent_complete, 2),
                'total_units' => (int) ($regionUnits->total_units ?? 0),
                'total_linear_ft' => (float) ($regionUnits->total_linear_ft ?? 0),
                'total_acres' => (float) $row->total_acres,
                'total_trees' => (int) ($regionUnits->total_trees ?? 0),
                'units_approved' => (int) ($regionUnits->units_approved ?? 0),
                'units_refused' => (int) ($regionUnits->units_refused ?? 0),
                'units_pending' => (int) ($regionUnits->units_pending ?? 0),
                'active_planners' => (int) ($plannerCounts[$row->region_id] ?? 0),
                'total_planner_days' => 0,
                'unit_counts_by_type' => [],
                'status_breakdown' => [
                    'ACTIV' => (int) $row->active_circuits,
                    'QC' => (int) $row->qc_circuits,
                    'CLOSE' => (int) $row->closed_circuits,
                    'REWRK' => (int) ($row->rework_circuits ?? 0),
                ],
                'daily_breakdown' => [],
            ];
        })->keyBy('region_id');
    }

    #[Computed]
    public function selectedRegion(): ?Region
    {
        return $this->selectedRegionId
            ? Region::find($this->selectedRegionId)
            : null;
    }

    /**
     * Get stats for the selected region.
     * Returns RegionalWeeklyAggregate or stdClass (from fallback computation).
     */
    #[Computed]
    public function selectedRegionStats(): ?object
    {
        return $this->selectedRegionId
            ? $this->regionStats[$this->selectedRegionId] ?? null
            : null;
    }

    #[Computed]
    public function sortedRegions(): Collection
    {
        $regions = $this->regions;
        $stats = $this->regionStats;

        $statColumns = [
            'active_circuits',
            'total_circuits',
            'total_miles',
            'miles_planned',
            'miles_remaining',
            'avg_percent_complete',
            'total_units',
            'active_planners',
        ];

        if (in_array($this->sortBy, $statColumns)) {
            return $regions->sortBy(function ($region) use ($stats) {
                return $stats[$region->id]->{$this->sortBy} ?? 0;
            }, SORT_NUMERIC, $this->sortDir === 'desc');
        }

        return $regions;
    }

    public function openPanel(int $regionId): void
    {
        $this->selectedRegionId = $regionId;
        $this->panelOpen = true;
        $this->dispatch('open-panel');
    }

    public function closePanel(): void
    {
        $this->panelOpen = false;
        $this->selectedRegionId = null;
        $this->dispatch('close-panel');
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    /**
     * Clear computed caches when circuit filters change.
     */
    protected function onCircuitFiltersUpdated(): void
    {
        unset(
            $this->regionStats,
            $this->sortedRegions,
            $this->selectedRegionStats,
            $this->availableCycleTypes
        );
    }

    public function render()
    {
        return view('livewire.assessments.dashboard.overview');
    }
}
