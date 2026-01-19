<?php

namespace App\Livewire\Admin;

use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('components.layout.app-shell', ['title' => 'User Management'])]
class UserManagement extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'role')]
    public string $roleFilter = '';

    // Create/Edit modal state
    public bool $showModal = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $selectedRole = '';

    public ?int $defaultRegionId = null;

    // Analytics exclusion state
    public ?int $excludingUserId = null;

    public string $exclusionReason = '';

    public bool $showExclusionModal = false;

    /**
     * Reset pagination when search changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Open modal to create a new user.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    /**
     * Open modal to edit a user.
     */
    public function edit(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->selectedRole = $user->roles->first()?->name ?? '';
        $this->defaultRegionId = $user->default_region_id;
        $this->showModal = true;
    }

    /**
     * Save the user (create or update).
     */
    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'selectedRole' => ['required', 'string', 'exists:roles,name'],
            'defaultRegionId' => ['nullable', 'exists:regions,id'],
        ];

        if ($this->editingUserId) {
            $rules['email'][] = 'unique:users,email,'.$this->editingUserId;
            if ($this->password) {
                $rules['password'] = ['string', 'min:8', Password::defaults()];
            }
        } else {
            $rules['email'][] = 'unique:users,email';
            $rules['password'] = ['required', 'string', 'min:8', Password::defaults()];
        }

        $this->validate($rules);

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
                'default_region_id' => $this->defaultRegionId,
            ]);

            if ($this->password) {
                $user->update(['password' => Hash::make($this->password)]);
            }

            $user->syncRoles([$this->selectedRole]);

            $this->dispatch('notify', message: 'User updated successfully.', type: 'success');
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'default_region_id' => $this->defaultRegionId,
            ]);

            $user->assignRole($this->selectedRole);

            $this->dispatch('notify', message: 'User created successfully.', type: 'success');
        }

        $this->closeModal();
    }

    /**
     * Delete a user.
     */
    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->dispatch('notify', message: 'You cannot delete your own account.', type: 'error');

            return;
        }

        $user->delete();
        $this->dispatch('notify', message: 'User deleted successfully.', type: 'success');
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset the form.
     */
    private function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->selectedRole = '';
        $this->defaultRegionId = null;
        $this->resetValidation();
    }

    /**
     * Get available roles.
     */
    public function getRolesProperty(): \Illuminate\Support\Collection
    {
        return Role::orderBy('name')->get();
    }

    /**
     * Get available regions.
     */
    public function getRegionsProperty(): \Illuminate\Support\Collection
    {
        return Region::active()->ordered()->get();
    }

    /**
     * Open exclusion modal for a user.
     */
    public function openExclusionModal(int $userId): void
    {
        $this->excludingUserId = $userId;
        $this->exclusionReason = '';
        $this->showExclusionModal = true;
    }

    /**
     * Close exclusion modal.
     */
    public function closeExclusionModal(): void
    {
        $this->excludingUserId = null;
        $this->exclusionReason = '';
        $this->showExclusionModal = false;
    }

    /**
     * Exclude a user from analytics.
     */
    public function excludeFromAnalytics(): void
    {
        if (empty(trim($this->exclusionReason))) {
            $this->dispatch('notify', message: 'Please provide a reason for exclusion.', type: 'warning');

            return;
        }

        $user = User::find($this->excludingUserId);
        if ($user) {
            $user->excludeFromAnalytics(trim($this->exclusionReason), auth()->user());
            $this->dispatch('notify', message: "{$user->name} excluded from analytics.", type: 'success');
        }

        $this->closeExclusionModal();
    }

    /**
     * Include a user back in analytics.
     */
    public function includeInAnalytics(int $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->includeInAnalytics();
            $this->dispatch('notify', message: "{$user->name} included in analytics.", type: 'success');
        }
    }

    public function render()
    {
        $users = User::query()
            ->with(['roles', 'defaultRegion', 'excludedByUser'])
            ->when($this->search, function ($q) {
                $search = '%'.strtolower($this->search).'%';
                $q->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                });
            })
            ->when($this->roleFilter, fn ($q) => $q->role($this->roleFilter))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.user-management', [
            'users' => $users,
        ]);
    }
}
