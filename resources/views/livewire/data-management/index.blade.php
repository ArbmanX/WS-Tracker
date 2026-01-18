<div class="container mx-auto p-6">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Data Management</h1>
        <p class="text-base-content/60">Manage circuit data, exclusions, and sync logs</p>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <x-ui.stat-card
            label="Total Circuits"
            :value="number_format($this->stats['total_circuits'])"
            icon="map"
            color="primary"
            size="sm"
        />
        <x-ui.stat-card
            label="Excluded"
            :value="number_format($this->stats['excluded_circuits'])"
            icon="eye-slash"
            color="warning"
            size="sm"
        />
        <x-ui.stat-card
            label="Modified"
            :value="number_format($this->stats['modified_circuits'])"
            icon="pencil-square"
            color="info"
            size="sm"
        />
        <x-ui.stat-card
            label="Syncs (24h)"
            :value="number_format($this->stats['syncs_24h'])"
            icon="arrow-path"
            color="success"
            size="sm"
        />
        <x-ui.stat-card
            label="Failed (24h)"
            :value="number_format($this->stats['failed_syncs_24h'])"
            icon="exclamation-triangle"
            color="error"
            size="sm"
        />
    </div>

    {{-- Navigation Cards --}}
    <div class="grid md:grid-cols-2 gap-6">
        @foreach($this->tabs as $tab)
            <a
                href="{{ route($tab['route']) }}"
                class="card bg-base-100 shadow-lg hover:shadow-xl transition-shadow cursor-pointer group"
                wire:key="tab-{{ $tab['route'] }}"
            >
                <div class="card-body">
                    <div class="flex items-start gap-4">
                        <div class="p-3 rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-primary-content transition-colors">
                            <x-dynamic-component :component="'heroicon-o-' . $tab['icon']" class="size-6" />
                        </div>
                        <div class="flex-1">
                            <h2 class="card-title text-base-content group-hover:text-primary transition-colors">
                                {{ $tab['name'] }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </h2>
                            <p class="text-base-content/60 text-sm">{{ $tab['description'] }}</p>
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
