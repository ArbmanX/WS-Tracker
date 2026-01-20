<div class="container mx-auto p-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm breadcrumbs">
            <ul>
                <li><a href="{{ route('admin.data') }}">Data Management</a></li>
                <li>Table Manager</li>
            </ul>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Table Manager</h1>
                <p class="text-base-content/60">View and clear API/sync data tables</p>
            </div>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <x-ui.stat-card
            label="Total Rows"
            :value="number_format($this->summary['total_rows'])"
            icon="circle-stack"
            color="primary"
            size="sm"
        />
        <x-ui.stat-card
            label="Tables with Data"
            :value="$this->summary['tables_with_data'] . ' / ' . count($this->tableStats)"
            icon="table-cells"
            color="info"
            size="sm"
        />
        <x-ui.stat-card
            label="Tables with User Changes"
            :value="$this->summary['tables_with_user_changes']"
            icon="exclamation-triangle"
            color="warning"
            size="sm"
        />
    </div>

    {{-- Bulk Actions Bar --}}
    <div class="card bg-base-200 mb-6">
        <div class="card-body py-3 flex-row items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="text-sm text-base-content/70">
                    {{ count($selectedTables) }} table(s) selected
                </span>
                <button
                    wire:click="selectAllTables"
                    class="btn btn-ghost btn-xs"
                >
                    Select All
                </button>
                @if(count($selectedTables) > 0)
                    <button
                        wire:click="clearSelection"
                        class="btn btn-ghost btn-xs"
                    >
                        Clear Selection
                    </button>
                @endif
            </div>
            <button
                wire:click="confirmBulkClear"
                class="btn btn-error btn-sm"
                @if(count($selectedTables) === 0) disabled @endif
            >
                <x-heroicon-o-trash class="size-4" />
                Clear Selected
            </button>
        </div>
    </div>

    {{-- Table Cards by Category --}}
    @foreach($this->groupedTables as $category => $tables)
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-base-content mb-4 flex items-center gap-2">
                @if($category === 'snapshots')
                    <x-heroicon-o-camera class="size-5 text-secondary" />
                @elseif($category === 'aggregates')
                    <x-heroicon-o-calculator class="size-5 text-accent" />
                @elseif($category === 'logs')
                    <x-heroicon-o-document-text class="size-5 text-info" />
                @endif
                {{ ucfirst($category) }}
            </h2>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($tables as $tableName => $table)
                    <div
                        wire:key="table-{{ $tableName }}"
                        class="card bg-base-100 shadow-md hover:shadow-lg transition-shadow {{ in_array($tableName, $selectedTables) ? 'ring-2 ring-primary' : '' }}"
                    >
                        <div class="card-body p-4">
                            {{-- Header with checkbox --}}
                            <div class="flex items-start justify-between">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        class="checkbox checkbox-primary checkbox-sm"
                                        wire:click="toggleTableSelection('{{ $tableName }}')"
                                        @checked(in_array($tableName, $selectedTables))
                                    />
                                    <span class="font-semibold text-base-content">{{ $table['label'] }}</span>
                                </label>
                                @if($table['has_user_changes'])
                                    <div class="tooltip tooltip-left" data-tip="Contains user-modified circuit data">
                                        <span class="badge badge-warning badge-sm gap-1">
                                            <x-heroicon-o-exclamation-triangle class="size-3" />
                                            User Data
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Description --}}
                            <p class="text-sm text-base-content/60 mt-1">{{ $table['description'] }}</p>

                            {{-- Stats --}}
                            <div class="mt-4 space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-base-content/70">Rows</span>
                                    <span class="font-mono font-medium {{ $table['row_count'] > 0 ? 'text-primary' : 'text-base-content/40' }}">
                                        {{ number_format($table['row_count']) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-base-content/70">Last Modified</span>
                                    <span class="text-base-content/60">
                                        @if($table['last_modified'])
                                            <span class="tooltip" data-tip="{{ \Carbon\Carbon::parse($table['last_modified'])->format('M d, Y g:i A') }}">
                                                {{ \Carbon\Carbon::parse($table['last_modified'])->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="italic">Never</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="card-actions justify-end mt-4">
                                <button
                                    wire:click="confirmClear('{{ $tableName }}')"
                                    class="btn btn-error btn-sm btn-outline"
                                    @if($table['row_count'] === 0) disabled @endif
                                >
                                    <x-heroicon-o-trash class="size-4" />
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Confirmation Modal --}}
    @if($showConfirmModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="text-lg font-bold text-error flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="size-6" />
                    Confirm Table Clear
                </h3>

                <div class="py-4">
                    <p class="text-base-content/80 mb-4">
                        @if($bulkMode)
                            You are about to clear <strong>{{ count($selectedTables) }} table(s)</strong>.
                        @else
                            You are about to clear the <strong>{{ $this->tablesToConfirm[$selectedTable]['label'] ?? $selectedTable }}</strong> table.
                        @endif
                        This action cannot be undone.
                    </p>

                    {{-- Tables to clear summary --}}
                    <div class="bg-base-200 rounded-lg p-4 max-h-60 overflow-y-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th class="text-right">Rows</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalRows = 0; @endphp
                                @foreach($this->tablesToConfirm as $tableName => $tableInfo)
                                    @php $totalRows += $tableInfo['row_count']; @endphp
                                    <tr>
                                        <td class="flex items-center gap-2">
                                            {{ $tableInfo['label'] }}
                                            @if($tableInfo['has_user_changes'])
                                                <span class="badge badge-warning badge-xs">User Data</span>
                                            @endif
                                        </td>
                                        <td class="text-right font-mono">{{ number_format($tableInfo['row_count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-bold">
                                    <td>Total</td>
                                    <td class="text-right font-mono text-error">{{ number_format($totalRows) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if(collect($this->tablesToConfirm)->contains('has_user_changes', true))
                        <div role="alert" class="alert alert-warning mt-4">
                            <x-heroicon-o-exclamation-triangle class="size-5" />
                            <span>One or more tables contain data related to user-modified circuits. Clearing these may affect reporting accuracy.</span>
                        </div>
                    @endif
                </div>

                <div class="modal-action">
                    <button wire:click="closeModal" class="btn btn-ghost">
                        Cancel
                    </button>
                    <button wire:click="clearTable" class="btn btn-error">
                        <x-heroicon-o-trash class="size-4" />
                        Clear {{ $bulkMode ? count($selectedTables) . ' Table(s)' : 'Table' }}
                    </button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button wire:click="closeModal">close</button>
            </form>
        </div>
    @endif
</div>
