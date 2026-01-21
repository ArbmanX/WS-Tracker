<div class="container mx-auto p-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">API vs Database Comparison</h1>
            <p class="text-base-content/60">Compare WorkStudio API response fields with stored database values</p>
        </div>

        <div class="flex items-center gap-3">
            <button
                wire:click="fetchApiData"
                wire:loading.attr="disabled"
                class="btn btn-primary"
            >
                <span wire:loading.remove wire:target="fetchApiData">
                    <x-heroicon-o-arrow-path class="size-5" />
                </span>
                <span wire:loading wire:target="fetchApiData" class="loading loading-spinner loading-sm"></span>
                Fetch API Data
            </button>
        </div>
    </div>

    {{-- Controls --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body">
            <h2 class="card-title text-lg">
                <x-heroicon-o-adjustments-horizontal class="size-5" />
                Filters & Settings
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                {{-- Domain Filter --}}
                <label class="form-control">
                    <div class="label">
                        <span class="label-text">Planner Domain Filter</span>
                    </div>
                    <input
                        type="text"
                        wire:model.live="domainFilter"
                        class="input input-bordered"
                        placeholder="e.g., ASPLUNDH"
                    />
                    <div class="label">
                        <span class="label-text-alt text-base-content/60">Filter SS_TAKENBY by domain prefix</span>
                    </div>
                </label>

                {{-- Limit --}}
                <label class="form-control">
                    <div class="label">
                        <span class="label-text">Results Limit</span>
                    </div>
                    <select wire:model.live="limit" class="select select-bordered">
                        <option value="5">5 circuits</option>
                        <option value="10">10 circuits</option>
                        <option value="25">25 circuits</option>
                        <option value="50">50 circuits</option>
                    </select>
                </label>
            </div>
        </div>
    </div>

    {{-- Field Selector --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body">
            <h2 class="card-title text-lg">
                <x-heroicon-o-table-cells class="size-5" />
                Select Fields to Compare
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                @foreach($this->fieldCategories as $category => $fields)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-semibold text-base-content capitalize">{{ $category }}</h3>
                            <button
                                wire:click="selectAllInCategory('{{ $category }}')"
                                class="btn btn-xs btn-ghost"
                            >
                                Select All
                            </button>
                        </div>
                        <div class="space-y-1">
                            @foreach($fields as $field => $config)
                                <label class="label cursor-pointer justify-start gap-2 py-1">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleField('{{ $field }}')"
                                        @checked(in_array($field, $selectedFields))
                                        class="checkbox checkbox-primary checkbox-sm"
                                    />
                                    <span class="label-text text-sm">
                                        {{ $config['label'] }}
                                        @if($config['db_column'])
                                            <span class="badge badge-success badge-xs ml-1">stored</span>
                                        @else
                                            <span class="badge badge-warning badge-xs ml-1">not stored</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Comparison Results --}}
    @if(count($apiData) > 0)
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <h2 class="card-title text-lg">
                    <x-heroicon-o-document-magnifying-glass class="size-5" />
                    Comparison Results
                    <span class="badge badge-neutral">{{ count($apiData) }} circuits</span>
                </h2>

                <div class="overflow-x-auto mt-4">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr class="bg-base-200">
                                <th class="sticky left-0 bg-base-200 z-10">Work Order</th>
                                <th>In DB?</th>
                                @foreach($selectedFields as $field)
                                    @if(isset(\App\Livewire\Assessments\DataComparison::API_FIELDS[$field]))
                                        <th colspan="2" class="text-center border-l border-base-300">
                                            {{ \App\Livewire\Assessments\DataComparison::API_FIELDS[$field]['label'] }}
                                        </th>
                                    @endif
                                @endforeach
                            </tr>
                            <tr class="text-xs">
                                <th class="sticky left-0 bg-base-100 z-10"></th>
                                <th></th>
                                @foreach($selectedFields as $field)
                                    @if(isset(\App\Livewire\Assessments\DataComparison::API_FIELDS[$field]))
                                        <th class="text-primary border-l border-base-300">API</th>
                                        <th class="text-secondary">DB</th>
                                    @endif
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->comparisonData as $row)
                                <tr wire:key="row-{{ $row['work_order'] }}">
                                    <td class="sticky left-0 bg-base-100 z-10 font-mono text-sm">
                                        {{ $row['work_order'] }}
                                    </td>
                                    <td>
                                        @if($row['has_db_record'])
                                            <span class="badge badge-success badge-sm">Yes</span>
                                        @else
                                            <span class="badge badge-error badge-sm">No</span>
                                        @endif
                                    </td>
                                    @foreach($row['fields'] as $fieldData)
                                        <td class="border-l border-base-300 text-xs max-w-32 truncate" title="{{ $fieldData['api_value'] }}">
                                            {{ is_array($fieldData['api_value']) ? json_encode($fieldData['api_value']) : Str::limit((string)$fieldData['api_value'], 30) }}
                                        </td>
                                        <td class="text-xs max-w-32 truncate @if(!$fieldData['matches']) bg-warning/20 @endif" title="{{ $fieldData['db_value'] }}">
                                            @if($fieldData['db_value'] === null)
                                                <span class="text-base-content/40 italic">
                                                    @if(!$fieldData['stored'])
                                                        not stored
                                                    @else
                                                        null
                                                    @endif
                                                </span>
                                            @else
                                                {{ Str::limit((string)$fieldData['db_value'], 30) }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Legend --}}
                <div class="flex flex-wrap gap-4 mt-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="badge badge-success badge-sm">stored</span>
                        <span class="text-base-content/60">Field has dedicated DB column</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-warning badge-sm">not stored</span>
                        <span class="text-base-content/60">Field only in api_data_json or not captured</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-warning/20 rounded"></span>
                        <span class="text-base-content/60">Values don't match</span>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <div class="text-center py-12">
                    <x-heroicon-o-cloud-arrow-down class="size-12 mx-auto mb-4 text-base-content/30" />
                    <h3 class="text-lg font-medium mb-1">No Data Loaded</h3>
                    <p class="text-base-content/60 mb-4">Click "Fetch API Data" to load circuits from the WorkStudio API and compare with database values.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Summary Statistics --}}
    @if(count($apiData) > 0)
        <div class="card bg-base-100 shadow-lg mt-6">
            <div class="card-body">
                <h2 class="card-title text-lg">
                    <x-heroicon-o-chart-bar class="size-5" />
                    Field Storage Summary
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    @php
                        $storedCount = collect(\App\Livewire\Assessments\DataComparison::API_FIELDS)
                            ->filter(fn($f) => $f['db_column'] !== null)->count();
                        $notStoredCount = collect(\App\Livewire\Assessments\DataComparison::API_FIELDS)
                            ->filter(fn($f) => $f['db_column'] === null)->count();
                    @endphp

                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Total API Fields</div>
                        <div class="stat-value text-2xl">{{ count(\App\Livewire\Assessments\DataComparison::API_FIELDS) }}</div>
                    </div>

                    <div class="stat bg-success/10 rounded-lg">
                        <div class="stat-title">Stored in DB</div>
                        <div class="stat-value text-2xl text-success">{{ $storedCount }}</div>
                    </div>

                    <div class="stat bg-warning/10 rounded-lg">
                        <div class="stat-title">Not Stored</div>
                        <div class="stat-value text-2xl text-warning">{{ $notStoredCount }}</div>
                    </div>

                    <div class="stat bg-info/10 rounded-lg">
                        <div class="stat-title">Selected Fields</div>
                        <div class="stat-value text-2xl text-info">{{ count($selectedFields) }}</div>
                    </div>
                </div>

                {{-- Recommendations --}}
                <div class="alert alert-info mt-4">
                    <x-heroicon-o-light-bulb class="size-5" />
                    <div>
                        <h3 class="font-bold">Key Fields for Planner Analytics</h3>
                        <ul class="text-sm list-disc list-inside mt-1">
                            <li><strong>VEGJOB_LENGTHCOMP</strong> - Completed miles (critical for weekly delta calculation)</li>
                            <li><strong>SS_EDITDATE</strong> - Last modified date (critical for data freshness validation)</li>
                            <li><strong>SS_TAKENBY</strong> - Planner identifier (currently in api_data_json)</li>
                            <li><strong>WSREQ_SYNCHVERSN</strong> - Sync version (useful for staleness detection)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
