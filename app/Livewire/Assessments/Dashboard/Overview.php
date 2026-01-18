<?php

namespace App\Livewire\Assessments\Dashboard;

use App\Models\Region;
use App\Models\RegionalWeeklyAggregate;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Overview'])]
class Overview extends Component
{
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
        $latestWeek = RegionalWeeklyAggregate::max('week_ending');

        if (! $latestWeek) {
            return collect();
        }

        return RegionalWeeklyAggregate::query()
            ->where('week_ending', $latestWeek)
            ->get()
            ->keyBy('region_id');
    }

    #[Computed]
    public function selectedRegion(): ?Region
    {
        return $this->selectedRegionId
            ? Region::find($this->selectedRegionId)
            : null;
    }

    #[Computed]
    public function selectedRegionStats(): ?RegionalWeeklyAggregate
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

    public function render()
    {
        return view('livewire.assessments.dashboard.overview');
    }
}
