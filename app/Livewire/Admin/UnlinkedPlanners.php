<?php

namespace App\Livewire\Admin;

use App\Models\UnlinkedPlanner;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout.app-shell', ['title' => 'Unlinked Planners'])]
class UnlinkedPlanners extends Component
{
    use WithPagination;

    public ?int $linkingPlannerId = null;

    public ?int $selectedUserId = null;

    public bool $showLinked = false;

    /**
     * Start linking a planner to a user.
     */
    public function startLinking(int $plannerId): void
    {
        $this->linkingPlannerId = $plannerId;
        $this->selectedUserId = null;
    }

    /**
     * Cancel the linking process.
     */
    public function cancelLinking(): void
    {
        $this->linkingPlannerId = null;
        $this->selectedUserId = null;
    }

    /**
     * Link the planner to the selected user.
     */
    public function linkPlanner(): void
    {
        if (! auth()->user()?->hasAnyRole(['sudo_admin', 'admin'])) {
            $this->dispatch('notify', message: 'You do not have permission to link planners.', type: 'error');

            return;
        }

        if (! $this->linkingPlannerId || ! $this->selectedUserId) {
            return;
        }

        $planner = UnlinkedPlanner::findOrFail($this->linkingPlannerId);
        $user = User::findOrFail($this->selectedUserId);

        $planner->linkToUser($user);

        $this->dispatch('notify', message: "Linked {$planner->display_name} to {$user->name}.", type: 'success');
        $this->cancelLinking();
    }

    /**
     * Create a new user from an unlinked planner.
     */
    public function createUser(int $plannerId): void
    {
        if (! auth()->user()?->hasRole('sudo_admin')) {
            $this->dispatch('notify', message: 'Only sudo admins can create users.', type: 'error');

            return;
        }

        $planner = UnlinkedPlanner::findOrFail($plannerId);

        // Extract name parts from display name
        $nameParts = explode(' ', $planner->display_name ?? $planner->ws_username);
        $name = $planner->display_name ?? $planner->ws_username;

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@placeholder.local',
            'password' => bcrypt(str()->random(32)),
            'ws_user_guid' => $planner->ws_user_guid,
            'ws_username' => $planner->ws_username,
            'is_ws_linked' => true,
            'ws_linked_at' => now(),
        ]);

        // Link the planner
        $planner->update([
            'linked_to_user_id' => $user->id,
            'linked_at' => now(),
        ]);

        $this->dispatch('notify', message: "Created user {$user->name} and linked to planner.", type: 'success');
    }

    /**
     * Get available users for linking.
     */
    public function getAvailableUsersProperty(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('is_ws_linked', false)
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        $planners = UnlinkedPlanner::query()
            ->when(! $this->showLinked, fn ($q) => $q->unlinked())
            ->when($this->showLinked, fn ($q) => $q->with('linkedUser'))
            ->orderByDesc('circuit_count')
            ->paginate(15);

        return view('livewire.admin.unlinked-planners', [
            'planners' => $planners,
        ]);
    }
}
