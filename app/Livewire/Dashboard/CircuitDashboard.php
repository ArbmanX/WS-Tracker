<?php

namespace App\Livewire\Dashboard;

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

    // Local state
    public bool $showClosedCircuits = false;

    /**
     * Mock regions for the filter dropdown.
     */
    #[Computed]
    public function regions(): Collection
    {
        return collect([
            ['id' => 'central', 'name' => 'Central', 'code' => 'CTR', 'circuits_count' => 12],
            ['id' => 'lancaster', 'name' => 'Lancaster', 'code' => 'LAN', 'circuits_count' => 18],
            ['id' => 'lehigh', 'name' => 'Lehigh', 'code' => 'LEH', 'circuits_count' => 15],
            ['id' => 'harrisburg', 'name' => 'Harrisburg', 'code' => 'HAR', 'circuits_count' => 9],
        ]);
    }

    /**
     * Mock planners for the filter dropdown.
     */
    #[Computed]
    public function planners(): Collection
    {
        return collect([
            ['id' => 1, 'name' => 'Derek Cinicola', 'initials' => 'DC'],
            ['id' => 2, 'name' => 'Paul Longenecker', 'initials' => 'PL'],
            ['id' => 3, 'name' => 'Mike Johnson', 'initials' => 'MJ'],
            ['id' => 4, 'name' => 'Sarah Williams', 'initials' => 'SW'],
            ['id' => 5, 'name' => 'Tom Anderson', 'initials' => 'TA'],
        ]);
    }

    /**
     * Mock circuits organized by workflow stage.
     */
    #[Computed]
    public function circuits(): Collection
    {
        $allCircuits = collect([
            // Active circuits
            [
                'id' => 1,
                'work_order' => '2025-1930',
                'extension' => '@',
                'title' => 'HATFIELD 69/12 KV 20-01 LINE',
                'region' => ['name' => 'Lehigh', 'code' => 'LEH'],
                'percent_complete' => 35,
                'miles_planned' => 4.38,
                'total_miles' => 14.93,
                'api_modified_date' => '12/05',
                'workflow_stage' => 'active',
                'planners' => [
                    ['name' => 'Derek Cinicola', 'initials' => 'DC'],
                ],
                'aggregate' => [
                    'total_units' => 127,
                    'total_linear_ft' => 12880,
                    'total_acres' => 1.42,
                    'units_approved' => 89,
                    'units_pending' => 32,
                    'units_refused' => 6,
                ],
            ],
            [
                'id' => 2,
                'work_order' => '2025-2077',
                'extension' => '@',
                'title' => 'EAST PETERSBURG 69/12 KV 15-05 LINE',
                'region' => ['name' => 'Lancaster', 'code' => 'LAN'],
                'percent_complete' => 100,
                'miles_planned' => 8.56,
                'total_miles' => 8.56,
                'api_modified_date' => '12/05',
                'workflow_stage' => 'active',
                'planners' => [
                    ['name' => 'Paul Longenecker', 'initials' => 'PL'],
                    ['name' => 'Derek Cinicola', 'initials' => 'DC'],
                ],
                'aggregate' => [
                    'total_units' => 243,
                    'total_linear_ft' => 13896,
                    'total_acres' => 0.53,
                    'units_approved' => 198,
                    'units_pending' => 41,
                    'units_refused' => 4,
                ],
            ],
            [
                'id' => 3,
                'work_order' => '2025-1845',
                'extension' => 'A',
                'title' => 'MILLERSBURG 69/12 KV 12-03 LINE',
                'region' => ['name' => 'Central', 'code' => 'CTR'],
                'percent_complete' => 67,
                'miles_planned' => 6.2,
                'total_miles' => 9.25,
                'api_modified_date' => '12/04',
                'workflow_stage' => 'active',
                'planners' => [
                    ['name' => 'Mike Johnson', 'initials' => 'MJ'],
                ],
                'aggregate' => [
                    'total_units' => 156,
                    'total_linear_ft' => 9840,
                    'total_acres' => 2.1,
                    'units_approved' => 112,
                    'units_pending' => 38,
                    'units_refused' => 6,
                ],
            ],
            [
                'id' => 4,
                'work_order' => '2025-2134',
                'extension' => '@',
                'title' => 'PALMYRA 138/12 KV 08-02 LINE',
                'region' => ['name' => 'Harrisburg', 'code' => 'HAR'],
                'percent_complete' => 22,
                'miles_planned' => 2.8,
                'total_miles' => 12.7,
                'api_modified_date' => '12/06',
                'workflow_stage' => 'active',
                'planners' => [
                    ['name' => 'Sarah Williams', 'initials' => 'SW'],
                    ['name' => 'Tom Anderson', 'initials' => 'TA'],
                ],
                'aggregate' => [
                    'total_units' => 78,
                    'total_linear_ft' => 4200,
                    'total_acres' => 0.8,
                    'units_approved' => 45,
                    'units_pending' => 28,
                    'units_refused' => 5,
                ],
            ],

            // Pending Permissions
            [
                'id' => 5,
                'work_order' => '2025-1756',
                'extension' => '@',
                'title' => 'ALLENTOWN 69/12 KV 04-01 LINE',
                'region' => ['name' => 'Lehigh', 'code' => 'LEH'],
                'percent_complete' => 85,
                'miles_planned' => 11.2,
                'total_miles' => 13.2,
                'api_modified_date' => '12/03',
                'workflow_stage' => 'pending_permissions',
                'planners' => [
                    ['name' => 'Derek Cinicola', 'initials' => 'DC'],
                ],
                'aggregate' => [
                    'total_units' => 312,
                    'total_linear_ft' => 18400,
                    'total_acres' => 3.2,
                    'units_approved' => 156,
                    'units_pending' => 142,
                    'units_refused' => 14,
                ],
            ],
            [
                'id' => 6,
                'work_order' => '2025-1892',
                'extension' => 'B',
                'title' => 'MANHEIM 69/12 KV 11-04 LINE',
                'region' => ['name' => 'Lancaster', 'code' => 'LAN'],
                'percent_complete' => 92,
                'miles_planned' => 7.4,
                'total_miles' => 8.0,
                'api_modified_date' => '12/02',
                'workflow_stage' => 'pending_permissions',
                'planners' => [
                    ['name' => 'Paul Longenecker', 'initials' => 'PL'],
                ],
                'aggregate' => [
                    'total_units' => 189,
                    'total_linear_ft' => 11200,
                    'total_acres' => 1.8,
                    'units_approved' => 134,
                    'units_pending' => 48,
                    'units_refused' => 7,
                ],
            ],

            // QC
            [
                'id' => 7,
                'work_order' => '2025-1623',
                'extension' => '@',
                'title' => 'HUMMELSTOWN 69/12 KV 07-02 LINE',
                'region' => ['name' => 'Harrisburg', 'code' => 'HAR'],
                'percent_complete' => 100,
                'miles_planned' => 5.8,
                'total_miles' => 5.8,
                'api_modified_date' => '11/28',
                'workflow_stage' => 'qc',
                'planners' => [
                    ['name' => 'Tom Anderson', 'initials' => 'TA'],
                ],
                'aggregate' => [
                    'total_units' => 145,
                    'total_linear_ft' => 8600,
                    'total_acres' => 1.2,
                    'units_approved' => 138,
                    'units_pending' => 5,
                    'units_refused' => 2,
                ],
            ],
            [
                'id' => 8,
                'work_order' => '2025-1534',
                'extension' => '@',
                'title' => 'EPHRATA 138/12 KV 09-03 LINE',
                'region' => ['name' => 'Lancaster', 'code' => 'LAN'],
                'percent_complete' => 100,
                'miles_planned' => 10.3,
                'total_miles' => 10.3,
                'api_modified_date' => '11/25',
                'workflow_stage' => 'qc',
                'planners' => [
                    ['name' => 'Mike Johnson', 'initials' => 'MJ'],
                    ['name' => 'Sarah Williams', 'initials' => 'SW'],
                ],
                'aggregate' => [
                    'total_units' => 267,
                    'total_linear_ft' => 15800,
                    'total_acres' => 2.4,
                    'units_approved' => 254,
                    'units_pending' => 10,
                    'units_refused' => 3,
                ],
            ],

            // Rework
            [
                'id' => 9,
                'work_order' => '2025-1412',
                'extension' => '@',
                'title' => 'KUTZTOWN 69/12 KV 06-01 LINE',
                'region' => ['name' => 'Lehigh', 'code' => 'LEH'],
                'percent_complete' => 78,
                'miles_planned' => 4.9,
                'total_miles' => 6.3,
                'api_modified_date' => '12/01',
                'workflow_stage' => 'rework',
                'planners' => [
                    ['name' => 'Derek Cinicola', 'initials' => 'DC'],
                ],
                'aggregate' => [
                    'total_units' => 134,
                    'total_linear_ft' => 7200,
                    'total_acres' => 1.1,
                    'units_approved' => 98,
                    'units_pending' => 24,
                    'units_refused' => 12,
                ],
            ],

            // Closed
            [
                'id' => 10,
                'work_order' => '2025-1289',
                'extension' => '@',
                'title' => 'LITITZ 69/12 KV 05-02 LINE',
                'region' => ['name' => 'Lancaster', 'code' => 'LAN'],
                'percent_complete' => 100,
                'miles_planned' => 7.2,
                'total_miles' => 7.2,
                'api_modified_date' => '11/15',
                'workflow_stage' => 'closed',
                'planners' => [
                    ['name' => 'Paul Longenecker', 'initials' => 'PL'],
                ],
                'aggregate' => [
                    'total_units' => 189,
                    'total_linear_ft' => 10800,
                    'total_acres' => 1.6,
                    'units_approved' => 182,
                    'units_pending' => 0,
                    'units_refused' => 7,
                ],
            ],
            [
                'id' => 11,
                'work_order' => '2025-1156',
                'extension' => '@',
                'title' => 'CARLISLE 138/12 KV 03-01 LINE',
                'region' => ['name' => 'Central', 'code' => 'CTR'],
                'percent_complete' => 100,
                'miles_planned' => 9.1,
                'total_miles' => 9.1,
                'api_modified_date' => '11/10',
                'workflow_stage' => 'closed',
                'planners' => [
                    ['name' => 'Mike Johnson', 'initials' => 'MJ'],
                    ['name' => 'Tom Anderson', 'initials' => 'TA'],
                ],
                'aggregate' => [
                    'total_units' => 234,
                    'total_linear_ft' => 13400,
                    'total_acres' => 2.0,
                    'units_approved' => 228,
                    'units_pending' => 0,
                    'units_refused' => 6,
                ],
            ],
        ]);

        // Apply region filter
        if ($this->regionFilter) {
            $allCircuits = $allCircuits->filter(
                fn ($c) => strtolower($c['region']['name']) === strtolower($this->regionFilter)
            );
        }

        // Apply planner filter
        if ($this->plannerFilter) {
            $allCircuits = $allCircuits->filter(
                fn ($c) => collect($c['planners'])->contains('name', $this->plannerFilter)
            );
        }

        // Filter closed circuits unless showing them
        if (! $this->showClosedCircuits) {
            $allCircuits = $allCircuits->filter(fn ($c) => $c['workflow_stage'] !== 'closed');
        }

        return $allCircuits->groupBy('workflow_stage');
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

    public function render()
    {
        return view('livewire.dashboard.circuit-dashboard');
    }
}
