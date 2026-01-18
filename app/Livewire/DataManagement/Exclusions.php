<?php

namespace App\Livewire\DataManagement;

use App\Models\Circuit;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout.app-shell', ['title' => 'Excluded Circuits'])]
class Exclusions extends Component
{
    use WithPagination;

    /**
     * Include (un-exclude) a circuit.
     */
    public function includeCircuit(int $circuitId): void
    {
        $circuit = Circuit::findOrFail($circuitId);
        $circuit->include();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($circuit)
            ->log('Circuit included in reporting');

        $this->dispatch('notify', message: 'Circuit "'.$circuit->display_work_order.'" included in reporting.', type: 'success');
    }

    /**
     * Include all excluded circuits.
     */
    public function includeAll(): void
    {
        $circuits = Circuit::excluded()->get();
        $count = $circuits->count();

        foreach ($circuits as $circuit) {
            $circuit->include();

            activity()
                ->causedBy(auth()->user())
                ->performedOn($circuit)
                ->log('Circuit included in reporting (bulk action)');
        }

        $this->dispatch('notify', message: "{$count} circuits included in reporting.", type: 'success');
    }

    /**
     * Get the count of excluded circuits.
     */
    public function getExcludedCountProperty(): int
    {
        return Circuit::excluded()->count();
    }

    public function render()
    {
        $circuits = Circuit::query()
            ->excluded()
            ->with(['region', 'excludedBy'])
            ->orderBy('excluded_at', 'desc')
            ->paginate(15);

        return view('livewire.data-management.exclusions', [
            'circuits' => $circuits,
        ]);
    }
}
