<div class="container mx-auto p-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm breadcrumbs">
            <ul>
                <li><a href="{{ route('admin.data') }}">Data Management</a></li>
                <li>Exclusions</li>
            </ul>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Excluded Circuits</h1>
                <p class="text-base-content/60">Circuits hidden from reporting and dashboards</p>
            </div>
            @if($this->excludedCount > 0)
                <button
                    wire:click="includeAll"
                    wire:confirm="Are you sure you want to include all {{ $this->excludedCount }} excluded circuits? This action will restore them to reporting."
                    class="btn btn-outline btn-success btn-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Include All ({{ $this->excludedCount }})
                </button>
            @endif
        </div>
    </div>

    {{-- Excluded Circuits Table --}}
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            @if($circuits->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-success/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-base-content/60 text-lg">No excluded circuits</p>
                    <p class="text-base-content/40 text-sm mt-1">All circuits are currently included in reporting.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Work Order</th>
                                <th>Title</th>
                                <th>Region</th>
                                <th>Excluded By</th>
                                <th>Excluded At</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($circuits as $circuit)
                                <tr class="hover" wire:key="excluded-{{ $circuit->id }}">
                                    <td>
                                        <span class="font-mono text-sm">{{ $circuit->display_work_order }}</span>
                                    </td>
                                    <td class="max-w-xs truncate" title="{{ $circuit->title }}">
                                        {{ Str::limit($circuit->title, 35) }}
                                    </td>
                                    <td class="text-sm">
                                        {{ $circuit->region?->name ?? '-' }}
                                    </td>
                                    <td>
                                        @if($circuit->excludedBy)
                                            <div class="flex items-center gap-2">
                                                <div class="avatar avatar-placeholder">
                                                    <div class="bg-primary text-primary-content w-6 rounded-full">
                                                        <span class="text-xs">{{ $circuit->excludedBy->initials() }}</span>
                                                    </div>
                                                </div>
                                                <span class="text-sm">{{ $circuit->excludedBy->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-base-content/40 text-sm">Unknown</span>
                                        @endif
                                    </td>
                                    <td class="text-sm">
                                        @if($circuit->excluded_at)
                                            <div class="tooltip" data-tip="{{ $circuit->excluded_at->format('M d, Y g:i A') }}">
                                                {{ $circuit->excluded_at->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-base-content/40">-</span>
                                        @endif
                                    </td>
                                    <td class="max-w-xs">
                                        @if($circuit->exclusion_reason)
                                            <div class="tooltip tooltip-left" data-tip="{{ $circuit->exclusion_reason }}">
                                                <span class="text-sm">{{ Str::limit($circuit->exclusion_reason, 30) }}</span>
                                            </div>
                                        @else
                                            <span class="text-base-content/40 text-sm italic">No reason provided</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button
                                            wire:click="includeCircuit({{ $circuit->id }})"
                                            class="btn btn-success btn-xs"
                                            title="Include in reporting"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Include
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $circuits->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
