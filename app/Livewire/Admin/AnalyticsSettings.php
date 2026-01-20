<?php

namespace App\Livewire\Admin;

use App\Models\AnalyticsSetting;
use App\Models\Circuit;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Analytics Settings'])]
class AnalyticsSettings extends Component
{
    public string $scopeYear = '';

    /** @var array<string> */
    public array $selectedCycleTypes = [];

    /** @var array<string> */
    public array $selectedContractors = [];

    public bool $allCycleTypes = true;

    public bool $allContractors = true;

    public function mount(): void
    {
        $settings = AnalyticsSetting::instance();

        $this->scopeYear = $settings->scope_year ?? date('Y');

        // Cycle types: null means all selected
        $this->allCycleTypes = $settings->selected_cycle_types === null;
        $this->selectedCycleTypes = $settings->selected_cycle_types ?? [];

        // Contractors: null means all selected
        $this->allContractors = $settings->selected_contractors === null;
        $this->selectedContractors = $settings->selected_contractors ?? [];
    }

    /**
     * Get available scope years from existing circuit data.
     *
     * @return array<string>
     */
    #[Computed]
    public function availableScopeYears(): array
    {
        $years = AnalyticsSetting::getAvailableScopeYears();

        // Always include current year even if no circuits exist
        $currentYear = date('Y');
        if (! in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        return $years;
    }

    /**
     * Get available cycle types from circuit data.
     *
     * @return array<string>
     */
    #[Computed]
    public function availableCycleTypes(): array
    {
        return AnalyticsSetting::getAvailableCycleTypes();
    }

    /**
     * Get available contractors from user data.
     *
     * @return array<string>
     */
    #[Computed]
    public function availableContractors(): array
    {
        return AnalyticsSetting::getAvailableContractors();
    }

    /**
     * Get the current settings for display.
     */
    #[Computed]
    public function currentSettings(): AnalyticsSetting
    {
        return AnalyticsSetting::instance();
    }

    /**
     * Handle "All Cycle Types" toggle.
     */
    public function updatedAllCycleTypes(): void
    {
        if ($this->allCycleTypes) {
            $this->selectedCycleTypes = [];
        }
    }

    /**
     * Handle cycle type selection changes.
     */
    public function updatedSelectedCycleTypes(): void
    {
        if (! empty($this->selectedCycleTypes)) {
            $this->allCycleTypes = false;
        }
    }

    /**
     * Handle "All Contractors" toggle.
     */
    public function updatedAllContractors(): void
    {
        if ($this->allContractors) {
            $this->selectedContractors = [];
        }
    }

    /**
     * Handle contractor selection changes.
     */
    public function updatedSelectedContractors(): void
    {
        if (! empty($this->selectedContractors)) {
            $this->allContractors = false;
        }
    }

    /**
     * Save the analytics settings.
     */
    public function save(): void
    {
        $user = auth()->user();

        if (! $user?->hasRole('sudo_admin')) {
            $this->dispatch('notify', message: 'Only sudo admins can modify analytics settings.', type: 'error');

            return;
        }

        AnalyticsSetting::updateSettings([
            'scope_year' => $this->scopeYear,
            'selected_cycle_types' => $this->allCycleTypes ? null : $this->selectedCycleTypes,
            'selected_contractors' => $this->allContractors ? null : $this->selectedContractors,
        ], $user);

        // Clear computed caches
        unset($this->currentSettings);

        $this->dispatch('notify', message: 'Analytics settings updated successfully.', type: 'success');
    }

    /**
     * Reset to defaults.
     */
    public function resetToDefaults(): void
    {
        $user = auth()->user();

        if (! $user?->hasRole('sudo_admin')) {
            $this->dispatch('notify', message: 'Only sudo admins can modify analytics settings.', type: 'error');

            return;
        }

        $this->scopeYear = date('Y');
        $this->allCycleTypes = true;
        $this->selectedCycleTypes = [];
        $this->allContractors = true;
        $this->selectedContractors = [];

        AnalyticsSetting::updateSettings([
            'scope_year' => $this->scopeYear,
            'selected_cycle_types' => null,
            'selected_contractors' => null,
        ], $user);

        unset($this->currentSettings);

        $this->dispatch('notify', message: 'Settings reset to defaults.', type: 'success');
    }

    /**
     * Get preview stats for current settings.
     *
     * @return array{circuits: int, planners: int}
     */
    #[Computed]
    public function previewStats(): array
    {
        $circuitQuery = Circuit::query()
            ->forScopeYear($this->scopeYear);

        if (! $this->allCycleTypes && ! empty($this->selectedCycleTypes)) {
            $circuitQuery->withCycleTypes($this->selectedCycleTypes);
        }

        $plannerQuery = User::query()
            ->role('planner')
            ->includedInAnalytics();

        if (! $this->allContractors && ! empty($this->selectedContractors)) {
            $plannerQuery->withContractor($this->selectedContractors);
        }

        return [
            'circuits' => $circuitQuery->count(),
            'planners' => $plannerQuery->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.analytics-settings');
    }
}
