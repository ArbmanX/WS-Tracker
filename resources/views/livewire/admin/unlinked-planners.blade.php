<div class="container mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Unlinked Planners</h1>
        <p class="text-base-content/60">Link WorkStudio planners to local user accounts</p>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body py-4">
            <label class="label cursor-pointer justify-start gap-3">
                <input
                    type="checkbox"
                    wire:model.live="showLinked"
                    class="checkbox checkbox-primary"
                />
                <span class="label-text">Show already linked planners</span>
            </label>
        </div>
    </div>

    {{-- Planners Table --}}
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            @if($planners->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-success mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-base-content/60">All planners are linked!</p>
                    <p class="text-sm text-base-content/40 mt-1">No unlinked WorkStudio planners found.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Display Name</th>
                                <th>WS Username</th>
                                <th>Circuits</th>
                                <th>First Seen</th>
                                <th>Last Seen</th>
                                @if($showLinked)
                                    <th>Linked To</th>
                                @endif
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($planners as $planner)
                                <tr class="hover" wire:key="planner-{{ $planner->id }}">
                                    <td class="font-medium">
                                        @if($editingDisplayNameId === $planner->id)
                                            {{-- Editing Mode --}}
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    wire:model="editDisplayName"
                                                    wire:keydown.enter="saveDisplayName"
                                                    wire:keydown.escape="cancelEditingDisplayName"
                                                    class="input input-bordered input-sm w-48"
                                                    placeholder="Enter display name..."
                                                    autofocus
                                                />
                                                <button
                                                    wire:click="saveDisplayName"
                                                    class="btn btn-success btn-xs btn-square"
                                                    title="Save"
                                                >
                                                    <x-heroicon-o-check class="size-4" />
                                                </button>
                                                <button
                                                    wire:click="cancelEditingDisplayName"
                                                    class="btn btn-ghost btn-xs btn-square"
                                                    title="Cancel"
                                                >
                                                    <x-heroicon-o-x-mark class="size-4" />
                                                </button>
                                            </div>
                                        @else
                                            {{-- Display Mode --}}
                                            <div class="flex items-center gap-2 group">
                                                <span>{{ $planner->display_name ?? 'Unknown' }}</span>
                                                <button
                                                    wire:click="startEditingDisplayName({{ $planner->id }})"
                                                    class="btn btn-ghost btn-xs btn-square opacity-0 group-hover:opacity-100 transition-opacity"
                                                    title="Edit display name"
                                                >
                                                    <x-heroicon-o-pencil-square class="size-4" />
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="font-mono text-sm text-base-content/70">
                                        {{ $planner->ws_username }}
                                    </td>
                                    <td>
                                        <span class="badge badge-neutral">{{ $planner->circuit_count ?? 0 }}</span>
                                    </td>
                                    <td class="text-sm text-base-content/60">
                                        {{ $planner->first_seen_at?->format('M d, Y') ?? '-' }}
                                    </td>
                                    <td class="text-sm text-base-content/60">
                                        {{ $planner->last_seen_at?->diffForHumans() ?? '-' }}
                                    </td>
                                    @if($showLinked)
                                        <td>
                                            @if($planner->linkedUser)
                                                <div class="flex items-center gap-2">
                                                    <div class="avatar placeholder">
                                                        <div class="bg-success text-success-content w-6 rounded-full">
                                                            <span class="text-xs">{{ $planner->linkedUser->initials() }}</span>
                                                        </div>
                                                    </div>
                                                    <span class="text-sm">{{ $planner->linkedUser->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-base-content/40">-</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        @if($planner->isLinked())
                                            <span class="badge badge-success badge-sm">Linked</span>
                                        @elseif($linkingPlannerId === $planner->id)
                                            {{-- Linking Mode --}}
                                            <div class="flex items-center gap-2">
                                                <select
                                                    wire:model="selectedUserId"
                                                    class="select select-bordered select-sm w-40"
                                                >
                                                    <option value="">Select user...</option>
                                                    @foreach($this->availableUsers as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button
                                                    wire:click="linkPlanner"
                                                    class="btn btn-success btn-sm"
                                                    @disabled(!$selectedUserId)
                                                >
                                                    Link
                                                </button>
                                                <button
                                                    wire:click="cancelLinking"
                                                    class="btn btn-ghost btn-sm"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        @else
                                            {{-- Action Buttons --}}
                                            <div class="flex gap-2">
                                                <button
                                                    wire:click="startLinking({{ $planner->id }})"
                                                    class="btn btn-primary btn-sm"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                    </svg>
                                                    Link
                                                </button>
                                                @if(auth()->user()?->hasRole('sudo_admin'))
                                                    <button
                                                        wire:click="createUser({{ $planner->id }})"
                                                        wire:confirm="Create a new user account for {{ $planner->display_name ?? $planner->ws_username }}?"
                                                        class="btn btn-secondary btn-sm"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                        </svg>
                                                        Create User
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $planners->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Help Card --}}
    <div class="card bg-base-200 mt-6">
        <div class="card-body">
            <h3 class="card-title text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                How Planner Assignment Works
            </h3>
            <div class="text-sm text-base-content/70 space-y-2">
                <p>
                    When circuits are synced from WorkStudio, planners are identified by their username
                    (e.g., <code class="text-xs bg-base-300 px-1 py-0.5 rounded">ASPLUNDH\cnewcombe</code>).
                    If a planner's username doesn't match any existing user, they appear here.
                </p>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Edit Display Name:</strong> Hover over a name and click the pencil icon to set a human-readable display name for the username.</li>
                    <li><strong>Link to User:</strong> Connect a WorkStudio username to an existing user account.</li>
                    <li><strong>Create User:</strong> Create a new user account from the planner's information.</li>
                </ul>
                <p class="text-base-content/50">
                    Display names are used in analytics and reports to show friendly names instead of usernames.
                </p>
            </div>
        </div>
    </div>
</div>
