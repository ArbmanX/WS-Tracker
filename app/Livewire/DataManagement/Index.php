<?php

namespace App\Livewire\DataManagement;

use App\Models\Circuit;
use App\Models\SyncLog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Data Management'])]
class Index extends Component
{
    /**
     * Get stats for display.
     *
     * @return array<string, int>
     */
    public function getStatsProperty(): array
    {
        return [
            'total_circuits' => Circuit::count(),
            'excluded_circuits' => Circuit::excluded()->count(),
            'modified_circuits' => Circuit::withUserModifications()->count(),
            'syncs_24h' => SyncLog::recent(24)->count(),
            'failed_syncs_24h' => SyncLog::recent(24)->failed()->count(),
        ];
    }

    /**
     * Navigation tabs for the data management section.
     *
     * @return array<int, array{name: string, route: string, icon: string, description: string}>
     */
    public function getTabsProperty(): array
    {
        return [
            [
                'name' => 'Circuit Browser',
                'route' => 'admin.data.circuits',
                'icon' => 'map',
                'description' => 'Browse, search, and edit circuit data',
            ],
            [
                'name' => 'Exclusions',
                'route' => 'admin.data.exclusions',
                'icon' => 'eye-slash',
                'description' => 'Manage excluded circuits',
            ],
            [
                'name' => 'Sync Logs',
                'route' => 'admin.data.sync-logs',
                'icon' => 'arrow-path',
                'description' => 'View detailed sync history',
            ],
            [
                'name' => 'API Endpoints',
                'route' => 'admin.data.endpoints',
                'icon' => 'server',
                'description' => 'View configured API endpoints',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.data-management.index');
    }
}
