<?php

namespace App\Livewire\Dashboard;

use App\Enums\WorkflowStage;
use App\Models\Circuit;
use App\Models\PlannerDailyAggregate;
use App\Models\Region;
use App\Models\RegionalDailyAggregate;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class CircuitDashboard extends Component
{
    // URL-backed filters (shareable URLs)
    #[Url(as: 'region', history: true)]
    public ?string $regionFilter = null;

    #[Url(as: 'planner', history: true)]
    public ?string $plannerFilter = null;

    #[Url(as: 'from', history: true)]
    public ?string $dateFrom = null;

    #[Url(as: 'to', history: true)]
    public ?string $dateTo = null;

    // View selection (kanban or analytics)
    #[Url(as: 'view', history: true)]
    public string $activeView = 'kanban';

    // Local state
    public bool $showClosedCircuits = false;

    /**
     * Get regions for the filter dropdown.
     */
    #[Computed]
    public function regions(): Collection
    {
        return Region::query()
            ->active()
            ->ordered()
            ->withCount(['circuits' => fn ($q) => $q->notExcluded()])
            ->get()
            ->map(fn ($region) => [
                'id' => $region->id,
                'name' => $region->name,
                'code' => $region->code,
                'circuits_count' => $region->circuits_count,
            ]);
    }

    /**
     * Get planners for the filter dropdown.
     */
    #[Computed]
    public function planners(): Collection
    {
        return User::query()
            ->whereHas('circuits', fn ($q) => $q->notExcluded())
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'initials' => $user->initials(),
            ]);
    }

    /**
     * Get circuits organized by workflow stage.
     */
    #[Computed]
    public function circuits(): Collection
    {
        $query = Circuit::query()
            ->notExcluded()
            ->with([
                'region',
                'uiState',
                'planners',
                'latestAggregate',
            ]);

        // Apply region filter
        if ($this->regionFilter) {
            $query->whereHas('region', fn ($q) => $q->where('name', $this->regionFilter));
        }

        // Apply planner filter
        if ($this->plannerFilter) {
            $query->whereHas('planners', fn ($q) => $q->where('name', $this->plannerFilter));
        }

        // Apply date filters
        if ($this->dateFrom) {
            $query->where('api_modified_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('api_modified_date', '<=', $this->dateTo);
        }

        $circuits = $query->get();

        // Transform circuits to array format expected by views
        $transformedCircuits = $circuits->map(function (Circuit $circuit) {
            $uiState = $circuit->uiState;
            $aggregate = $circuit->latestAggregate;

            // Determine workflow stage (default to active if no UI state)
            $workflowStage = $uiState?->workflow_stage?->value ?? WorkflowStage::Active->value;

            return [
                'id' => $circuit->id,
                'work_order' => $circuit->work_order,
                'extension' => $circuit->extension ?? '@',
                'title' => $circuit->title,
                'region' => [
                    'name' => $circuit->region?->name ?? 'Unknown',
                    'code' => $circuit->region?->code ?? '?',
                ],
                'percent_complete' => (int) round($circuit->percent_complete ?? 0),
                'miles_planned' => (float) ($circuit->miles_planned ?? 0),
                'total_miles' => (float) ($circuit->total_miles ?? 0),
                'api_modified_date' => $circuit->api_modified_date?->format('m/d') ?? '',
                'workflow_stage' => $workflowStage,
                'planners' => $circuit->planners->map(fn ($planner) => [
                    'name' => $planner->name,
                    'initials' => $planner->initials(),
                ])->toArray(),
                'aggregate' => [
                    'total_units' => $aggregate?->total_units ?? 0,
                    'total_linear_ft' => (float) ($aggregate?->total_linear_ft ?? 0),
                    'total_acres' => (float) ($aggregate?->total_acres ?? 0),
                    'units_approved' => $aggregate?->units_approved ?? 0,
                    'units_pending' => $aggregate?->units_pending ?? 0,
                    'units_refused' => $aggregate?->units_refused ?? 0,
                ],
            ];
        });

        // Filter closed circuits unless showing them
        if (! $this->showClosedCircuits) {
            $transformedCircuits = $transformedCircuits->filter(
                fn ($c) => $c['workflow_stage'] !== WorkflowStage::Closed->value
            );
        }

        return $transformedCircuits->groupBy('workflow_stage');
    }

    /**
     * Compute aggregate stats for the dashboard.
     */
    #[Computed]
    public function stats(): array
    {
        $allCircuits = $this->circuits->flatten(1);

        return [
            'total_circuits' => $allCircuits->count(),
            'total_miles_planned' => round($allCircuits->sum('miles_planned'), 1),
            'total_miles' => round($allCircuits->sum('total_miles'), 1),
            'avg_completion' => round($allCircuits->avg('percent_complete'), 0),
            'active_planners' => $allCircuits->pluck('planners')->flatten(1)->unique('name')->count(),
            'permission_summary' => [
                'approved' => $allCircuits->sum(fn ($c) => $c['aggregate']['units_approved']),
                'pending' => $allCircuits->sum(fn ($c) => $c['aggregate']['units_pending']),
                'refused' => $allCircuits->sum(fn ($c) => $c['aggregate']['units_refused']),
            ],
        ];
    }

    /**
     * Handle circuit moved between workflow stages.
     */
    public function handleCircuitMoved(int $circuitId, string $newStage, int $newPosition): void
    {
        // In production, this would update the database
        // For now, we just dispatch an event for visual feedback
        $this->dispatch('notify', message: "Circuit #{$circuitId} moved to {$newStage}", type: 'success');
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->reset(['regionFilter', 'plannerFilter', 'dateFrom', 'dateTo']);
    }

    /**
     * Toggle showing closed circuits.
     */
    public function toggleClosedCircuits(): void
    {
        $this->showClosedCircuits = ! $this->showClosedCircuits;
    }

    /**
     * Switch between kanban and analytics views.
     */
    public function setView(string $view): void
    {
        $this->activeView = $view;
    }

    /**
     * Get regional performance data for analytics view.
     */
    #[Computed]
    public function regionalPerformance(): Collection
    {
        $colors = ['primary', 'secondary', 'accent', 'info', 'success', 'warning'];

        // Get latest aggregate for each region
        $latestAggregates = RegionalDailyAggregate::query()
            ->latestPerRegion()
            ->with('region')
            ->get()
            ->keyBy('region_id');

        // Get aggregates from 7 days ago for comparison
        $weekAgoDate = now()->subDays(7)->toDateString();
        $weekAgoAggregates = RegionalDailyAggregate::query()
            ->forDate($weekAgoDate)
            ->get()
            ->keyBy('region_id');

        return Region::query()
            ->active()
            ->ordered()
            ->get()
            ->values()
            ->map(function ($region, $index) use ($latestAggregates, $weekAgoAggregates, $colors) {
                $latest = $latestAggregates->get($region->id);
                $weekAgo = $weekAgoAggregates->get($region->id);

                // Calculate progress percentage
                $progress = 0;
                if ($latest && $latest->total_miles > 0) {
                    $progress = (int) round(($latest->miles_planned / $latest->total_miles) * 100);
                }

                // Calculate week-over-week change
                $change = 0;
                if ($weekAgo && $weekAgo->miles_planned > 0 && $latest) {
                    $change = (int) round((($latest->miles_planned - $weekAgo->miles_planned) / $weekAgo->miles_planned) * 100);
                }

                return [
                    'name' => $region->name,
                    'code' => substr($region->code ?? $region->name, 0, 2),
                    'color' => $colors[$index % count($colors)],
                    'active_circuits' => $latest?->active_circuits ?? 0,
                    'miles_planned' => round((float) ($latest?->miles_planned ?? 0), 1),
                    'progress' => $progress,
                    'change' => ($change >= 0 ? '+' : '').$change.'%',
                    'positive' => $change >= 0,
                ];
            });
    }

    /**
     * Get top planners data for analytics view.
     */
    #[Computed]
    public function topPlanners(): Collection
    {
        $colors = ['primary', 'secondary', 'accent', 'info', 'success'];

        // Get planners with the most units assessed in the last 30 days
        $startDate = now()->subDays(30)->toDateString();
        $endDate = now()->toDateString();

        return PlannerDailyAggregate::query()
            ->betweenDates($startDate, $endDate)
            ->selectRaw('user_id, SUM(total_units_assessed) as total_units, SUM(total_linear_ft) as total_linear_ft, SUM(circuits_worked) as total_circuits')
            ->groupBy('user_id')
            ->orderByDesc('total_units')
            ->limit(5)
            ->with('user.defaultRegion')
            ->get()
            ->values()
            ->map(function ($agg, $index) use ($colors) {
                $user = $agg->user;

                return [
                    'name' => $user?->name ?? 'Unknown',
                    'initials' => $user?->initials() ?? '?',
                    'region' => $user?->defaultRegion?->name ?? 'Multiple',
                    'color' => $colors[$index % count($colors)],
                    'units' => (int) $agg->total_units,
                    'linear_ft' => (int) round($agg->total_linear_ft),
                    'circuits' => (int) $agg->total_circuits,
                ];
            });
    }

    /**
     * Get recent circuit activity for analytics view.
     */
    #[Computed]
    public function recentActivity(): Collection
    {
        return Circuit::query()
            ->notExcluded()
            ->with(['region', 'uiState'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function (Circuit $circuit) {
                $stage = $circuit->uiState?->workflow_stage ?? WorkflowStage::Active;
                if (is_string($stage)) {
                    $stage = WorkflowStage::from($stage);
                }

                return [
                    'work_order' => $circuit->work_order,
                    'title' => $circuit->title,
                    'region' => $circuit->region?->name ?? 'Unknown',
                    'status' => $stage->label(),
                    'status_color' => $stage->color(),
                    'progress' => (int) round($circuit->percent_complete ?? 0),
                    'miles' => round((float) ($circuit->total_miles ?? 0), 1),
                    'updated' => $circuit->updated_at?->diffForHumans() ?? '',
                ];
            });
    }

    public function render()
    {
        return view('livewire.dashboard.circuit-dashboard');
    }
}
