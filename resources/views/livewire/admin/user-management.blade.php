<div class="container mx-auto p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-base-content">User Management</h1>
            <p class="text-base-content/60">Manage user accounts, roles, and analytics exclusions</p>
        </div>
        <div class="flex gap-2">
            @if(Route::has('admin.planners'))
                <a href="{{ route('admin.planners') }}" class="btn btn-outline btn-sm">
                    <x-heroicon-o-users class="h-4 w-4" />
                    Manage Planners
                </a>
            @endif
            <button wire:click="create" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add User
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body py-4">
            <div class="flex flex-wrap gap-4 items-end">
                {{-- Search --}}
                <div class="form-control flex-1 min-w-[200px]">
                    <label class="label">
                        <span class="label-text text-xs">Search</span>
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name or email..."
                        class="input input-bordered input-sm"
                    />
                </div>

                {{-- Role Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Role</span>
                    </label>
                    <select wire:model.live="roleFilter" class="select select-bordered select-sm">
                        <option value="">All Roles</option>
                        @foreach($this->roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            @if($users->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-base-content/40">No users found</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Region</th>
                                <th>WS Linked</th>
                                <th>Analytics</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr class="hover" wire:key="user-{{ $user->id }}">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="avatar avatar-placeholder">
                                                <div class="bg-primary text-primary-content w-10 rounded-full">
                                                    <span>{{ $user->initials() }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-medium">{{ $user->name }}</div>
                                                @if($user->id === auth()->id())
                                                    <span class="badge badge-ghost badge-xs">You</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-sm">{{ $user->email }}</td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            <span class="badge badge-{{ $role->name === 'sudo_admin' ? 'error' : ($role->name === 'admin' ? 'warning' : 'info') }} badge-sm">
                                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="text-sm">
                                        {{ $user->defaultRegion?->name ?? '-' }}
                                    </td>
                                    <td>
                                        @if($user->is_ws_linked)
                                            <span class="badge badge-success badge-sm">Linked</span>
                                        @else
                                            <span class="badge badge-ghost badge-sm">Not Linked</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Analytics status (only for planners) --}}
                                        @if($user->hasRole('planner'))
                                            @if($user->is_excluded_from_analytics)
                                                <div class="tooltip tooltip-left" data-tip="{{ $user->exclusion_reason }}">
                                                    <span class="badge badge-warning badge-sm gap-1">
                                                        <x-heroicon-o-eye-slash class="h-3 w-3" />
                                                        Excluded
                                                    </span>
                                                </div>
                                            @else
                                                <span class="badge badge-success badge-sm gap-1">
                                                    <x-heroicon-o-check class="h-3 w-3" />
                                                    Active
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-base-content/30">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex gap-1">
                                            <button
                                                wire:click="edit({{ $user->id }})"
                                                class="btn btn-ghost btn-xs"
                                                title="Edit user"
                                            >
                                                <x-heroicon-o-pencil class="h-4 w-4" />
                                            </button>
                                            {{-- Analytics toggle for planners --}}
                                            @if($user->hasRole('planner'))
                                                @if($user->is_excluded_from_analytics)
                                                    <button
                                                        wire:click="includeInAnalytics({{ $user->id }})"
                                                        wire:confirm="Include {{ $user->name }} back in analytics?"
                                                        class="btn btn-ghost btn-xs text-success"
                                                        title="Include in analytics"
                                                    >
                                                        <x-heroicon-o-eye class="h-4 w-4" />
                                                    </button>
                                                @else
                                                    <button
                                                        wire:click="openExclusionModal({{ $user->id }})"
                                                        class="btn btn-ghost btn-xs text-warning"
                                                        title="Exclude from analytics"
                                                    >
                                                        <x-heroicon-o-eye-slash class="h-4 w-4" />
                                                    </button>
                                                @endif
                                            @endif
                                            @if($user->id !== auth()->id())
                                                <button
                                                    wire:click="delete({{ $user->id }})"
                                                    wire:confirm="Are you sure you want to delete {{ $user->name }}? This action cannot be undone."
                                                    class="btn btn-ghost btn-xs text-error"
                                                    title="Delete user"
                                                >
                                                    <x-heroicon-o-trash class="h-4 w-4" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <dialog class="modal {{ $showModal ? 'modal-open' : '' }}">
        <div class="modal-box">
            <form wire:submit="save">
                <h3 class="font-bold text-lg mb-4">
                    {{ $editingUserId ? 'Edit User' : 'Create User' }}
                </h3>

                {{-- Name --}}
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Name</span>
                    </label>
                    <input
                        type="text"
                        wire:model="name"
                        class="input input-bordered @error('name') input-error @enderror"
                        placeholder="Full name"
                    />
                    @error('name')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Email</span>
                    </label>
                    <input
                        type="email"
                        wire:model="email"
                        class="input input-bordered @error('email') input-error @enderror"
                        placeholder="email@example.com"
                    />
                    @error('email')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">
                            Password
                            @if($editingUserId)
                                <span class="text-base-content/60">(leave blank to keep current)</span>
                            @endif
                        </span>
                    </label>
                    <input
                        type="password"
                        wire:model="password"
                        class="input input-bordered @error('password') input-error @enderror"
                        placeholder="{{ $editingUserId ? '••••••••' : 'Min 8 characters' }}"
                    />
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- Role --}}
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Role</span>
                    </label>
                    <select
                        wire:model="selectedRole"
                        class="select select-bordered @error('selectedRole') select-error @enderror"
                    >
                        <option value="">Select a role...</option>
                        @foreach($this->roles as $role)
                            <option value="{{ $role->name }}">
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('selectedRole')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- Default Region --}}
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Default Region</span>
                    </label>
                    <select
                        wire:model="defaultRegionId"
                        class="select select-bordered"
                    >
                        <option value="">No default region</option>
                        @foreach($this->regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="closeModal" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        {{ $editingUserId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button wire:click="closeModal">close</button>
        </form>
    </dialog>

    {{-- Exclusion Modal --}}
    @if($showExclusionModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Exclude from Analytics</h3>
                <p class="py-4 text-base-content/60">
                    This user will be hidden from all analytics, reports, and dashboards.
                </p>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Reason for exclusion <span class="text-error">*</span></span>
                    </label>
                    <textarea
                        wire:model="exclusionReason"
                        class="textarea textarea-bordered"
                        placeholder="e.g., Contractor no longer active, Test account, etc."
                        rows="3"
                    ></textarea>
                </div>

                <div class="modal-action">
                    <button wire:click="closeExclusionModal" class="btn btn-ghost">Cancel</button>
                    <button wire:click="excludeFromAnalytics" class="btn btn-warning">
                        <x-heroicon-o-eye-slash class="h-4 w-4" />
                        Exclude
                    </button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button wire:click="closeExclusionModal">close</button>
            </form>
        </div>
    @endif
</div>
