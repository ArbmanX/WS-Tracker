{{-- Analytics View --}}
<div class="p-6 overflow-y-auto h-full">
    {{-- Key Metrics Row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Metric 1: Total Miles --}}
        <div class="tooltip" data-tip="Total miles of vegetation work planned across all active circuits">
            <div class="metric-card card bg-base-100 border border-base-200 shadow-sm cursor-default">
                <div class="card-body p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-base-content/60 mb-1">Miles Planned</p>
                            <p class="text-3xl font-bold text-base-content">{{ number_format($this->stats['total_miles_planned'], 1) }}</p>
                            <div class="flex items-center gap-1 mt-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <span class="text-sm text-success font-medium">+12.5%</span>
                                <span class="text-xs text-base-content/50">vs last week</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Metric 2: Active Circuits --}}
        <div class="tooltip" data-tip="Number of circuits currently in active work status">
            <div class="metric-card card bg-base-100 border border-base-200 shadow-sm cursor-default">
                <div class="card-body p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-base-content/60 mb-1">Active Circuits</p>
                            <p class="text-3xl font-bold text-base-content">{{ $this->stats['total_circuits'] }}</p>
                            <div class="flex items-center gap-1 mt-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <span class="text-sm text-success font-medium">+3</span>
                                <span class="text-xs text-base-content/50">new this week</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Metric 3: Completion Rate --}}
        <div class="tooltip" data-tip="Average completion percentage across all active circuits">
            <div class="metric-card card bg-base-100 border border-base-200 shadow-sm cursor-default">
                <div class="card-body p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-base-content/60 mb-1">Avg Completion</p>
                            <p class="text-3xl font-bold text-base-content">{{ $this->stats['avg_completion'] }}%</p>
                            <div class="flex items-center gap-1 mt-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <span class="text-sm text-success font-medium">+5.2%</span>
                                <span class="text-xs text-base-content/50">vs last week</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Metric 4: Units Assessed --}}
        <div class="tooltip" data-tip="Total vegetation units assessed across all circuits">
            <div class="metric-card card bg-base-100 border border-base-200 shadow-sm cursor-default">
                <div class="card-body p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-base-content/60 mb-1">Units Assessed</p>
                            <p class="text-3xl font-bold text-base-content">{{ number_format($this->stats['permission_summary']['approved'] + $this->stats['permission_summary']['pending'] + $this->stats['permission_summary']['refused']) }}</p>
                            <div class="flex items-center gap-1 mt-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                </svg>
                                <span class="text-sm text-error font-medium">-2.1%</span>
                                <span class="text-xs text-base-content/50">vs last week</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Main Chart: Weekly Progress (Bar Chart) --}}
        <div class="lg:col-span-2 card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-lg">Weekly Progress</h3>
                    <div class="flex gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-primary"></div>
                            <span class="text-base-content/70">Miles</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-secondary"></div>
                            <span class="text-base-content/70">Units</span>
                        </div>
                    </div>
                </div>

                @php
                    $weeklyChartOptions = [
                        'xaxis' => [
                            'categories' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                            'labels' => ['style' => ['fontSize' => '12px']],
                        ],
                        'yaxis' => [
                            ['title' => ['text' => 'Miles'], 'labels' => ['formatter' => 'function(val) { return val.toFixed(0) }']],
                            ['opposite' => true, 'title' => ['text' => 'Units'], 'labels' => ['formatter' => 'function(val) { return val.toFixed(0) }']],
                        ],
                        'plotOptions' => [
                            'bar' => ['columnWidth' => '60%', 'borderRadius' => 4],
                        ],
                        'dataLabels' => ['enabled' => false],
                        'stroke' => ['show' => true, 'width' => 2, 'colors' => ['transparent']],
                        'legend' => ['show' => false],
                    ];
                    $weeklyChartSeries = [
                        ['name' => 'Miles', 'data' => [12.5, 18.2, 9.8, 22.1, 15.4, 4.2, 2.1]],
                        ['name' => 'Units', 'data' => [45, 62, 38, 78, 55, 15, 8]],
                    ];
                @endphp

                <x-apex-chart
                    chart-id="weekly-progress-chart"
                    type="bar"
                    :height="280"
                    :options="$weeklyChartOptions"
                    :series="$weeklyChartSeries"
                />
            </div>
        </div>

        {{-- Permission Status (Donut Chart) --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body">
                <h3 class="font-semibold text-lg mb-2">Permission Status</h3>

                @php
                    $approved = $this->stats['permission_summary']['approved'];
                    $pending = $this->stats['permission_summary']['pending'];
                    $refused = $this->stats['permission_summary']['refused'];
                    $total = $approved + $pending + $refused;

                    $permissionChartOptions = [
                        'labels' => ['Approved', 'Pending', 'Refused'],
                        'colors' => ['#22c55e', '#eab308', '#ef4444'],
                        'legend' => ['position' => 'bottom', 'fontSize' => '13px'],
                        'plotOptions' => [
                            'pie' => [
                                'donut' => [
                                    'size' => '65%',
                                    'labels' => [
                                        'show' => true,
                                        'name' => ['fontSize' => '14px'],
                                        'value' => ['fontSize' => '20px', 'fontWeight' => 'bold'],
                                        'total' => [
                                            'show' => true,
                                            'label' => 'Total',
                                            'fontSize' => '12px',
                                            'formatter' => 'function(w) { return w.globals.seriesTotals.reduce((a, b) => a + b, 0) }',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'dataLabels' => [
                            'enabled' => true,
                            'formatter' => 'function(val) { return Math.round(val) + "%" }',
                        ],
                        'responsive' => [
                            ['breakpoint' => 480, 'options' => ['chart' => ['height' => 300], 'legend' => ['position' => 'bottom']]],
                        ],
                    ];
                    $permissionChartSeries = [$approved ?: 1, $pending ?: 1, $refused ?: 1];
                @endphp

                <x-apex-chart
                    chart-id="permission-status-chart"
                    type="donut"
                    :height="280"
                    :options="$permissionChartOptions"
                    :series="$permissionChartSeries"
                />
            </div>
        </div>
    </div>

    {{-- Second Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Circuit Status Distribution (Radial Bar) --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body">
                <h3 class="font-semibold text-lg mb-2">Circuit Status Distribution</h3>

                @php
                    $activeCount = $this->circuits->get('active', collect())->count();
                    $pendingCount = $this->circuits->get('pending_permissions', collect())->count();
                    $qcCount = $this->circuits->get('qc', collect())->count();
                    $reworkCount = $this->circuits->get('rework', collect())->count();
                    $closedCount = $this->circuits->get('closed', collect())->count();
                    $totalCircuits = $this->stats['total_circuits'] ?: 1;

                    $statusChartOptions = [
                        'labels' => ['Active', 'Pending', 'QC Review', 'Rework', 'Closed'],
                        'colors' => ['#3b82f6', '#eab308', '#06b6d4', '#ef4444', '#22c55e'],
                        'plotOptions' => [
                            'radialBar' => [
                                'dataLabels' => [
                                    'name' => ['fontSize' => '14px'],
                                    'value' => ['fontSize' => '16px', 'formatter' => 'function(val) { return Math.round(val) + "%" }'],
                                    'total' => [
                                        'show' => true,
                                        'label' => 'Total',
                                        'formatter' => 'function(w) { return "' . $totalCircuits . '" }',
                                    ],
                                ],
                                'hollow' => ['size' => '40%'],
                                'track' => ['background' => 'transparent'],
                            ],
                        ],
                        'legend' => ['show' => true, 'position' => 'bottom', 'fontSize' => '12px'],
                    ];
                    $statusChartSeries = [
                        $totalCircuits > 0 ? round(($activeCount / $totalCircuits) * 100) : 0,
                        $totalCircuits > 0 ? round(($pendingCount / $totalCircuits) * 100) : 0,
                        $totalCircuits > 0 ? round(($qcCount / $totalCircuits) * 100) : 0,
                        $totalCircuits > 0 ? round(($reworkCount / $totalCircuits) * 100) : 0,
                        $totalCircuits > 0 ? round(($closedCount / $totalCircuits) * 100) : 0,
                    ];
                @endphp

                <x-apex-chart
                    chart-id="status-distribution-chart"
                    type="radialBar"
                    :height="320"
                    :options="$statusChartOptions"
                    :series="$statusChartSeries"
                />
            </div>
        </div>

        {{-- Miles by Region (Horizontal Bar Chart) --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body">
                <h3 class="font-semibold text-lg mb-2">Miles by Region</h3>

                @php
                    $regionData = $this->regionalPerformance;
                    $regionNames = collect($regionData)->pluck('name')->toArray();
                    $regionMiles = collect($regionData)->pluck('miles_planned')->toArray();
                    $regionProgress = collect($regionData)->pluck('progress')->toArray();

                    $regionChartOptions = [
                        'xaxis' => [
                            'categories' => $regionNames,
                        ],
                        'plotOptions' => [
                            'bar' => ['horizontal' => true, 'borderRadius' => 4, 'barHeight' => '70%'],
                        ],
                        'dataLabels' => [
                            'enabled' => true,
                            'formatter' => 'function(val) { return val.toFixed(1) + " mi" }',
                            'style' => ['fontSize' => '12px'],
                        ],
                        'legend' => ['show' => false],
                        'tooltip' => [
                            'y' => ['formatter' => 'function(val) { return val.toFixed(1) + " miles" }'],
                        ],
                    ];
                    $regionChartSeries = [
                        ['name' => 'Miles Planned', 'data' => $regionMiles],
                    ];
                @endphp

                <x-apex-chart
                    chart-id="miles-by-region-chart"
                    type="bar"
                    :height="320"
                    :options="$regionChartOptions"
                    :series="$regionChartSeries"
                />
            </div>
        </div>
    </div>

    {{-- Top Planners & Recent Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Top Planners --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg">Top Planners This Week</h3>
                    <button class="btn btn-ghost btn-sm">View All</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Planner</th>
                                <th class="text-right">
                                    <div class="tooltip tooltip-left" data-tip="Total vegetation units assessed">Units</div>
                                </th>
                                <th class="text-right">
                                    <div class="tooltip tooltip-left" data-tip="Total linear feet of line trimming">Linear Ft</div>
                                </th>
                                <th class="text-right">
                                    <div class="tooltip tooltip-left" data-tip="Number of circuits assigned">Circuits</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->topPlanners as $planner)
                                <tr class="hover">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="avatar placeholder">
                                                <div class="bg-{{ $planner['color'] }} text-{{ $planner['color'] }}-content w-8 rounded-full">
                                                    <span class="text-xs">{{ $planner['initials'] }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-medium">{{ $planner['name'] }}</div>
                                                <div class="text-xs text-base-content/60">{{ $planner['region'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right font-medium">{{ number_format($planner['units']) }}</td>
                                    <td class="text-right">{{ number_format($planner['linear_ft']) }}</td>
                                    <td class="text-right">{{ $planner['circuits'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Planner Activity (Area Chart) --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body">
                <h3 class="font-semibold text-lg mb-2">Planner Activity Trend</h3>

                @php
                    $activityChartOptions = [
                        'xaxis' => [
                            'categories' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                            'labels' => ['style' => ['fontSize' => '12px']],
                        ],
                        'yaxis' => [
                            'title' => ['text' => 'Units Assessed'],
                        ],
                        'stroke' => ['curve' => 'smooth', 'width' => 2],
                        'fill' => [
                            'type' => 'gradient',
                            'gradient' => [
                                'shadeIntensity' => 1,
                                'opacityFrom' => 0.5,
                                'opacityTo' => 0.1,
                            ],
                        ],
                        'dataLabels' => ['enabled' => false],
                        'legend' => ['position' => 'top'],
                    ];
                    $activityChartSeries = [
                        ['name' => 'Central', 'data' => [120, 145, 132, 168]],
                        ['name' => 'Lancaster', 'data' => [95, 110, 125, 140]],
                        ['name' => 'Lehigh', 'data' => [80, 92, 88, 105]],
                    ];
                @endphp

                <x-apex-chart
                    chart-id="planner-activity-chart"
                    type="area"
                    :height="280"
                    :options="$activityChartOptions"
                    :series="$activityChartSeries"
                />
            </div>
        </div>
    </div>

    {{-- Recent Activity Table --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">Recent Circuit Activity</h3>
                <div class="flex gap-2">
                    <button class="btn btn-ghost btn-sm btn-active">All</button>
                    <button class="btn btn-ghost btn-sm">Status Changes</button>
                    <button class="btn btn-ghost btn-sm">Completions</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Circuit</th>
                            <th>Region</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Miles</th>
                            <th>Last Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->recentActivity as $activity)
                            <tr class="hover">
                                <td>
                                    <div class="font-medium">{{ $activity['work_order'] }}</div>
                                    <div class="text-xs text-base-content/60">{{ $activity['title'] }}</div>
                                </td>
                                <td>
                                    <div class="badge badge-outline badge-sm">{{ $activity['region'] }}</div>
                                </td>
                                <td>
                                    <div class="badge badge-{{ $activity['status_color'] }} badge-sm">{{ $activity['status'] }}</div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <progress class="progress progress-{{ $activity['status_color'] }} w-20 h-2" value="{{ $activity['progress'] }}" max="100"></progress>
                                        <span class="text-sm">{{ $activity['progress'] }}%</span>
                                    </div>
                                </td>
                                <td>{{ $activity['miles'] }}</td>
                                <td class="text-sm text-base-content/60">{{ $activity['updated'] }}</td>
                                <td>
                                    <button class="btn btn-ghost btn-xs">View</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="flex justify-between items-center mt-4">
                <p class="text-sm text-base-content/60">Showing 1-{{ count($this->recentActivity) }} of {{ $this->stats['total_circuits'] }} circuits</p>
                <div class="join">
                    <button class="join-item btn btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button class="join-item btn btn-sm btn-active">1</button>
                    <button class="join-item btn btn-sm">2</button>
                    <button class="join-item btn btn-sm">3</button>
                    <button class="join-item btn btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .metric-card {
        transition: all 0.2s ease;
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.1);
    }
</style>
