# Phase 1F: Charts & Visualization

> **Goal:** Implement dashboard charts using ApexCharts with Alpine.js integration.
> **Estimated Time:** 2 days
> **Dependencies:** Phase 1E (dashboard exists), Phase 1C (aggregates populated)

---

## Status: Not Started

| Item | Status | Notes |
|------|--------|-------|
| ApexChart wrapper | Needed | Reusable Alpine component |
| MilesByRegionChart | Needed | Bar chart |
| PlannerProgressChart | Needed | Line/area chart |
| PermissionStatusChart | Needed | Donut chart |
| Progress indicators | Needed | On circuit cards |

---

## Chart Types

| Chart | Type | Purpose |
|-------|------|---------|
| Miles by Region | Bar | Compare regions |
| Planner Progress | Line | Track productivity over time |
| Permission Status | Donut | Show approval breakdown |
| Circuit Progress | Area | Show completion trends |

---

## Checklist

### Components
- [ ] Create reusable apex-chart Blade component
- [ ] Create MilesByRegionChart Livewire component
- [ ] Create PlannerProgressChart Livewire component
- [ ] Create PermissionStatusChart Livewire component
- [ ] Create CircuitProgressChart Livewire component

### Integration
- [ ] Add charts to dashboard layout
- [ ] Implement data aggregation queries
- [ ] Support theme-aware colors
- [ ] Add loading states
- [ ] Implement responsive sizing

### Testing
- [ ] Write data calculation tests
- [ ] Write browser tests for chart rendering

---

## File Structure

```
app/Livewire/Charts/
    ├── MilesByRegionChart.php
    ├── PlannerProgressChart.php
    ├── PermissionStatusChart.php
    └── CircuitProgressChart.php

resources/views/
    ├── components/
    │   └── apex-chart.blade.php
    └── livewire/charts/
        ├── miles-by-region-chart.blade.php
        ├── planner-progress-chart.blade.php
        ├── permission-status-chart.blade.php
        └── circuit-progress-chart.blade.php
```

---

## Key Implementation Details

### Reusable ApexChart Component

```blade
{{-- resources/views/components/apex-chart.blade.php --}}
@props([
    'chartId' => 'chart-' . uniqid(),
    'type' => 'bar',
    'height' => '350',
    'options' => [],
    'series' => [],
])

<div
    x-data="apexChartComponent({
        chartId: '{{ $chartId }}',
        type: '{{ $type }}',
        height: {{ $height }},
        options: @js($options),
        series: @js($series)
    })"
    x-init="init()"
    class="w-full"
>
    <div
        id="{{ $chartId }}"
        wire:ignore
        class="min-h-[{{ $height }}px]"
    ></div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('apexChartComponent', (config) => ({
        chart: null,

        init() {
            this.renderChart();

            // Re-render on theme change
            window.addEventListener('theme-changed', () => {
                this.chart?.destroy();
                this.renderChart();
            });

            // Re-render on Livewire update
            Livewire.on('chart-updated', (data) => {
                if (data.chartId === config.chartId) {
                    this.updateChart(data.series, data.options);
                }
            });
        },

        renderChart() {
            const isDark = document.documentElement.getAttribute('data-theme')?.includes('dark');

            const defaultOptions = {
                chart: {
                    type: config.type,
                    height: config.height,
                    background: 'transparent',
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 500 }
                },
                theme: {
                    mode: isDark ? 'dark' : 'light',
                },
                colors: this.getThemeColors(),
                ...config.options
            };

            this.chart = new ApexCharts(
                document.getElementById(config.chartId),
                { ...defaultOptions, series: config.series }
            );
            this.chart.render();
        },

        updateChart(series, options) {
            if (this.chart) {
                this.chart.updateSeries(series);
                if (options) {
                    this.chart.updateOptions(options);
                }
            }
        },

        getThemeColors() {
            const style = getComputedStyle(document.documentElement);
            return [
                style.getPropertyValue('--color-primary') || '#1882C5',
                style.getPropertyValue('--color-secondary') || '#28317E',
                style.getPropertyValue('--color-accent') || '#E27434',
                style.getPropertyValue('--color-success') || '#22c55e',
                style.getPropertyValue('--color-warning') || '#eab308',
            ];
        },

        destroy() {
            this.chart?.destroy();
        }
    }));
});
</script>
@endpush
```

### MilesByRegionChart Livewire Component

```php
<?php

namespace App\Livewire\Charts;

use App\Models\Circuit;
use App\Models\Region;
use Livewire\Component;
use Livewire\Attributes\Computed;

class MilesByRegionChart extends Component
{
    public function getChartData(): array
    {
        $data = Region::withSum('circuits', 'total_miles')
            ->withSum('circuits', 'miles_planned')
            ->orderBy('name')
            ->get();

        return [
            'categories' => $data->pluck('name')->toArray(),
            'series' => [
                [
                    'name' => 'Total Miles',
                    'data' => $data->pluck('circuits_sum_total_miles')->map(fn($v) => round($v ?? 0, 2))->toArray(),
                ],
                [
                    'name' => 'Miles Planned',
                    'data' => $data->pluck('circuits_sum_miles_planned')->map(fn($v) => round($v ?? 0, 2))->toArray(),
                ],
            ],
        ];
    }

    #[Computed]
    public function chartOptions(): array
    {
        $data = $this->getChartData();

        return [
            'xaxis' => [
                'categories' => $data['categories'],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '55%',
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'stroke' => [
                'show' => true,
                'width' => 2,
                'colors' => ['transparent'],
            ],
            'yaxis' => [
                'title' => ['text' => 'Miles'],
            ],
            'tooltip' => [
                'y' => [
                    'formatter' => "function(val) { return val + ' mi' }",
                ],
            ],
        ];
    }

    #[Computed]
    public function chartSeries(): array
    {
        return $this->getChartData()['series'];
    }

    public function render()
    {
        return view('livewire.charts.miles-by-region-chart');
    }
}
```

### MilesByRegionChart Blade View

```blade
{{-- resources/views/livewire/charts/miles-by-region-chart.blade.php --}}
<div class="card bg-base-100 shadow">
    <div class="card-body">
        <h3 class="card-title text-sm">Miles by Region</h3>

        <x-apex-chart
            chart-id="miles-by-region"
            type="bar"
            :height="300"
            :options="$this->chartOptions"
            :series="$this->chartSeries"
        />
    </div>
</div>
```

### PermissionStatusChart Component

```php
<?php

namespace App\Livewire\Charts;

use App\Models\CircuitAggregate;
use Livewire\Component;
use Livewire\Attributes\Computed;

class PermissionStatusChart extends Component
{
    public ?int $circuitId = null;

    public function getChartData(): array
    {
        $query = CircuitAggregate::query()
            ->selectRaw('
                SUM(units_approved) as approved,
                SUM(units_refused) as refused,
                SUM(units_pending) as pending
            ')
            ->where('is_rollup', false);

        if ($this->circuitId) {
            $query->where('circuit_id', $this->circuitId);
        }

        $totals = $query->first();

        return [
            'labels' => ['Approved', 'Refused', 'Pending'],
            'series' => [
                (int) ($totals->approved ?? 0),
                (int) ($totals->refused ?? 0),
                (int) ($totals->pending ?? 0),
            ],
        ];
    }

    #[Computed]
    public function chartOptions(): array
    {
        $data = $this->getChartData();

        return [
            'labels' => $data['labels'],
            'colors' => ['#22c55e', '#ef4444', '#eab308'],
            'legend' => [
                'position' => 'bottom',
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '65%',
                        'labels' => [
                            'show' => true,
                            'total' => [
                                'show' => true,
                                'label' => 'Total Units',
                            ],
                        ],
                    ],
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'formatter' => "function(val) { return Math.round(val) + '%' }",
            ],
        ];
    }

    #[Computed]
    public function chartSeries(): array
    {
        return $this->getChartData()['series'];
    }

    public function render()
    {
        return view('livewire.charts.permission-status-chart');
    }
}
```

### PlannerProgressChart Component

```php
<?php

namespace App\Livewire\Charts;

use App\Models\PlannerDailyAggregate;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Computed;

class PlannerProgressChart extends Component
{
    public ?int $userId = null;
    public int $daysBack = 30;

    public function getChartData(): array
    {
        $startDate = now()->subDays($this->daysBack);

        $query = PlannerDailyAggregate::query()
            ->where('aggregate_date', '>=', $startDate)
            ->orderBy('aggregate_date');

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $data = $query
            ->selectRaw('
                aggregate_date,
                SUM(total_units_assessed) as units,
                SUM(total_linear_ft) as linear_ft
            ')
            ->groupBy('aggregate_date')
            ->get();

        return [
            'categories' => $data->pluck('aggregate_date')
                ->map(fn($d) => Carbon::parse($d)->format('M d'))
                ->toArray(),
            'series' => [
                [
                    'name' => 'Units Assessed',
                    'data' => $data->pluck('units')->toArray(),
                ],
                [
                    'name' => 'Linear Feet',
                    'data' => $data->pluck('linear_ft')->toArray(),
                ],
            ],
        ];
    }

    #[Computed]
    public function chartOptions(): array
    {
        $data = $this->getChartData();

        return [
            'xaxis' => [
                'categories' => $data['categories'],
                'type' => 'category',
            ],
            'yaxis' => [
                [
                    'title' => ['text' => 'Units'],
                ],
                [
                    'opposite' => true,
                    'title' => ['text' => 'Linear Feet'],
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 1,
                    'opacityFrom' => 0.5,
                    'opacityTo' => 0.1,
                ],
            ],
        ];
    }

    #[Computed]
    public function chartSeries(): array
    {
        return $this->getChartData()['series'];
    }

    public function render()
    {
        return view('livewire.charts.planner-progress-chart');
    }
}
```

### Dashboard Integration

```blade
{{-- In circuit-dashboard.blade.php --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <livewire:charts.miles-by-region-chart />
    <livewire:charts.permission-status-chart />
    <livewire:charts.planner-progress-chart :days-back="30" />
</div>

{{-- Collapsible charts panel --}}
<div
    x-data="{ expanded: true }"
    class="collapse collapse-arrow bg-base-100 shadow mb-6"
>
    <input type="checkbox" x-model="expanded" />
    <div class="collapse-title text-lg font-medium">
        Charts & Analytics
    </div>
    <div class="collapse-content">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <livewire:charts.circuit-progress-chart />
            <livewire:charts.planner-progress-chart />
        </div>
    </div>
</div>
```

---

## Testing Requirements

```php
// tests/Feature/Charts/MilesByRegionChartTest.php
it('calculates totals by region', function () {
    $region = Region::factory()->create(['name' => 'Central']);
    Circuit::factory()->count(3)->create([
        'region_id' => $region->id,
        'total_miles' => 10,
        'miles_planned' => 5,
    ]);

    Livewire::test(MilesByRegionChart::class)
        ->assertSet('chartSeries.0.data.0', 30.0)
        ->assertSet('chartSeries.1.data.0', 15.0);
});

it('returns data in ApexCharts format', function () {
    $region = Region::factory()->create();

    $component = Livewire::test(MilesByRegionChart::class);

    $options = $component->get('chartOptions');
    $series = $component->get('chartSeries');

    expect($options)->toHaveKey('xaxis.categories');
    expect($series)->toBeArray();
    expect($series[0])->toHaveKeys(['name', 'data']);
});

// tests/Feature/Charts/PermissionStatusChartTest.php
it('calculates permission breakdowns', function () {
    CircuitAggregate::factory()->create([
        'units_approved' => 50,
        'units_refused' => 10,
        'units_pending' => 40,
    ]);

    Livewire::test(PermissionStatusChart::class)
        ->assertSet('chartSeries', [50, 10, 40]);
});

// tests/Browser/ChartDisplayTest.php (Pest 4)
it('renders chart on dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $page = visit('/dashboard')->actingAs($user);

    $page->waitFor('#miles-by-region')
        ->assertPresent('.apexcharts-canvas')
        ->assertNoJavascriptErrors();
});

it('updates chart on filter change', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $page = visit('/dashboard')->actingAs($user);

    $page->select('regionFilter', 'Central')
        ->waitFor('.apexcharts-canvas')
        ->assertNoJavascriptErrors();
});
```

---

## Next Phase

Once all items are checked, proceed to **[Phase 1G: Admin Features](./PHASE_1G_ADMIN_FEATURES.md)**.
