<?php

namespace App\Livewire\DataManagement;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Table Manager'])]
class TableManager extends Component
{
    public bool $showConfirmModal = false;

    public ?string $selectedTable = null;

    public bool $bulkMode = false;

    public array $selectedTables = [];

    /**
     * Tables that can be cleared (API/sync data only).
     * Key = table name, value = config array.
     *
     * @var array<string, array{label: string, description: string, category: string}>
     */
    protected array $clearableTables = [
        'circuit_snapshots' => [
            'label' => 'Circuit Snapshots',
            'description' => 'Daily snapshots of circuit state',
            'category' => 'snapshots',
        ],
        'planned_units_snapshots' => [
            'label' => 'Planned Units Snapshots',
            'description' => 'Snapshots of planned unit data from API',
            'category' => 'snapshots',
        ],
        'circuit_aggregates' => [
            'label' => 'Circuit Aggregates',
            'description' => 'Pre-computed circuit metrics',
            'category' => 'aggregates',
        ],
        'planner_daily_aggregates' => [
            'label' => 'Planner Daily Aggregates',
            'description' => 'Daily planner productivity stats',
            'category' => 'aggregates',
        ],
        'planner_weekly_aggregates' => [
            'label' => 'Planner Weekly Aggregates',
            'description' => 'Weekly planner productivity stats',
            'category' => 'aggregates',
        ],
        'regional_daily_aggregates' => [
            'label' => 'Regional Daily Aggregates',
            'description' => 'Daily regional rollup stats',
            'category' => 'aggregates',
        ],
        'regional_weekly_aggregates' => [
            'label' => 'Regional Weekly Aggregates',
            'description' => 'Weekly regional rollup stats',
            'category' => 'aggregates',
        ],
        'sync_logs' => [
            'label' => 'Sync Logs',
            'description' => 'API sync history and audit trail',
            'category' => 'logs',
        ],
    ];

    /**
     * Get table statistics for display.
     *
     * @return array<string, array{label: string, description: string, category: string, row_count: int, last_modified: ?string, has_user_changes: bool}>
     */
    #[Computed]
    public function tableStats(): array
    {
        $stats = [];

        foreach ($this->clearableTables as $table => $config) {
            $stats[$table] = [
                'label' => $config['label'],
                'description' => $config['description'],
                'category' => $config['category'],
                'row_count' => DB::table($table)->count(),
                'last_modified' => DB::table($table)->max('updated_at'),
                'has_user_changes' => $this->checkForUserChanges($table),
            ];
        }

        return $stats;
    }

    /**
     * Check if a table has related user changes that could be affected.
     */
    protected function checkForUserChanges(string $table): bool
    {
        // Tables that relate to circuits with user modifications
        $circuitRelatedTables = [
            'circuit_snapshots',
            'circuit_aggregates',
            'planned_units_snapshots',
        ];

        if (in_array($table, $circuitRelatedTables)) {
            // Use database-agnostic JSON check
            $driver = DB::connection()->getDriverName();

            $query = DB::table('circuits')
                ->whereNotNull('user_modified_fields');

            if ($driver === 'pgsql') {
                $query->whereRaw("user_modified_fields::text != '{}'");
            } elseif ($driver === 'sqlite') {
                $query->whereRaw("json(user_modified_fields) != json('{}')");
            } else {
                // MySQL/MariaDB
                $query->whereRaw('JSON_LENGTH(user_modified_fields) > 0');
            }

            return $query->exists();
        }

        return false;
    }

    /**
     * Get total counts for summary display.
     *
     * @return array{total_rows: int, tables_with_data: int, tables_with_user_changes: int}
     */
    #[Computed]
    public function summary(): array
    {
        $stats = $this->tableStats;

        return [
            'total_rows' => collect($stats)->sum('row_count'),
            'tables_with_data' => collect($stats)->filter(fn ($s) => $s['row_count'] > 0)->count(),
            'tables_with_user_changes' => collect($stats)->filter(fn ($s) => $s['has_user_changes'])->count(),
        ];
    }

    /**
     * Group tables by category.
     *
     * @return array<string, array<string, array>>
     */
    #[Computed]
    public function groupedTables(): array
    {
        $stats = $this->tableStats;

        return collect($stats)
            ->groupBy('category')
            ->map(fn ($items) => $items->toArray())
            ->toArray();
    }

    /**
     * Open confirm modal for a single table.
     */
    public function confirmClear(string $table): void
    {
        $this->selectedTable = $table;
        $this->bulkMode = false;
        $this->showConfirmModal = true;
    }

    /**
     * Open confirm modal for bulk clear.
     */
    public function confirmBulkClear(): void
    {
        if (empty($this->selectedTables)) {
            $this->dispatch('notify', message: 'Please select at least one table', type: 'warning');

            return;
        }

        $this->bulkMode = true;
        $this->showConfirmModal = true;
    }

    /**
     * Toggle table selection for bulk operations.
     */
    public function toggleTableSelection(string $table): void
    {
        if (in_array($table, $this->selectedTables)) {
            $this->selectedTables = array_diff($this->selectedTables, [$table]);
        } else {
            $this->selectedTables[] = $table;
        }
    }

    /**
     * Select all tables.
     */
    public function selectAllTables(): void
    {
        $this->selectedTables = array_keys($this->clearableTables);
    }

    /**
     * Clear selection.
     */
    public function clearSelection(): void
    {
        $this->selectedTables = [];
    }

    /**
     * Clear the selected table(s).
     */
    public function clearTable(): void
    {
        $tablesToClear = $this->bulkMode ? $this->selectedTables : [$this->selectedTable];

        if (empty($tablesToClear)) {
            return;
        }

        $clearedCounts = [];

        foreach ($tablesToClear as $table) {
            if (! array_key_exists($table, $this->clearableTables)) {
                continue;
            }

            $count = DB::table($table)->count();
            DB::table($table)->truncate();
            $clearedCounts[$table] = $count;
        }

        // Log the activity
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'tables' => $tablesToClear,
                'cleared_counts' => $clearedCounts,
                'total_rows_cleared' => array_sum($clearedCounts),
            ])
            ->log('Truncated database tables: '.implode(', ', $tablesToClear));

        $totalCleared = array_sum($clearedCounts);
        $tableCount = count($tablesToClear);

        $this->dispatch('notify',
            message: "Cleared {$totalCleared} rows from {$tableCount} table(s)",
            type: 'success'
        );

        $this->closeModal();
        unset($this->tableStats, $this->summary, $this->groupedTables);
    }

    /**
     * Close the confirmation modal.
     */
    public function closeModal(): void
    {
        $this->showConfirmModal = false;
        $this->selectedTable = null;
        $this->bulkMode = false;
    }

    /**
     * Get the tables to be cleared for the modal display.
     *
     * @return array<string, array>
     */
    public function getTablesToConfirmProperty(): array
    {
        $tables = $this->bulkMode ? $this->selectedTables : ($this->selectedTable ? [$this->selectedTable] : []);

        return collect($tables)
            ->mapWithKeys(fn ($table) => [$table => $this->tableStats[$table] ?? null])
            ->filter()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.data-management.table-manager');
    }
}
