<div>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Leafwire Map Demo</h1>
            <p class="text-base-content/60">Click anywhere on the map to place markers. Drag them around.</p>
        </div>

        <div class="flex items-center gap-3">
            @if($markerCount > 0)
                <span class="badge badge-primary">{{ $markerCount }} marker{{ $markerCount !== 1 ? 's' : '' }}</span>
                <button wire:click="clearAllMarkers" class="btn btn-error btn-sm btn-outline">
                    Clear All
                </button>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-4">
        {{-- Map (takes 3 cols) --}}
        <div class="lg:col-span-3">
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body p-0 overflow-hidden rounded-2xl">
                    {{-- Map Container --}}
                    <div
                        id="{{ $mapId }}"
                        class="{{ $containerClass }}"
                        style="{{ $containerStyle }}"
                        wire:ignore
                    ></div>
                </div>
            </div>
        </div>

        {{-- Sidebar controls --}}
        <div class="space-y-4">
            {{-- Selected Location --}}
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <h2 class="card-title text-sm">
                        <x-heroicon-o-map-pin class="size-4" />
                        Last Click
                    </h2>
                    @if($selectedLocation)
                        <div class="font-mono text-sm space-y-1">
                            <div class="flex justify-between">
                                <span class="text-base-content/60">Lat</span>
                                <span>{{ $selectedLocation['lat'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-base-content/60">Lng</span>
                                <span>{{ $selectedLocation['lng'] }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-base-content/40 text-sm">Click the map</p>
                    @endif
                </div>
            </div>

            {{-- Tile Provider Switcher --}}
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <h2 class="card-title text-sm">
                        <x-heroicon-o-squares-2x2 class="size-4" />
                        Tile Provider
                    </h2>
                    <div class="flex flex-col gap-1">
                        @foreach(['openstreetmap' => 'OpenStreetMap', 'cartodb_positron' => 'CartoDB Light', 'cartodb_dark' => 'CartoDB Dark', 'esri_world_imagery' => 'Esri Satellite'] as $key => $label)
                            <button
                                wire:click="switchTiles('{{ $key }}')"
                                class="btn btn-sm {{ $tileProvider === $key ? 'btn-primary' : 'btn-ghost' }} justify-start"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Map Info --}}
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <h2 class="card-title text-sm">
                        <x-heroicon-o-information-circle class="size-4" />
                        Map State
                    </h2>
                    <div class="font-mono text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-base-content/60">Zoom</span>
                            <span>{{ $zoom }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">Center</span>
                            <span>{{ round($center[0], 2) }}, {{ round($center[1], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Initialize map - @script runs after component is mounted, DOM is already ready --}}
    @script
    <script>
        if (typeof window.Leafwire !== 'undefined') {
            window.Leafwire.init('{{ $mapId }}', @json($this->getMapConfig()));
        }

        $wire.on('leafwire:reinitialize', () => {
            if (typeof window.Leafwire !== 'undefined') {
                window.Leafwire.destroy('{{ $mapId }}');
                window.Leafwire.init('{{ $mapId }}', @json($this->getMapConfig()));
            }
        });
    </script>
    @endscript
</div>
