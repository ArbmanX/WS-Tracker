<?php

namespace App\Livewire;

use ArbmanX\Leafwire\Components\Map;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('components.layout.app-shell', ['title' => 'Map Demo'])]
class MapDemo extends Map
{
    public ?array $selectedLocation = null;

    public int $markerCount = 0;

    public function mount(
        ?string $mapId = null,
        ?array $center = null,
        ?int $zoom = null,
        ?string $tileProvider = null,
        ?string $class = null,
        ?string $style = null,
        array $plugins = [],
        array $options = [],
    ): void {
        parent::mount(
            mapId: $mapId,
            center: $center ?? [39.8283, -98.5795],
            zoom: $zoom ?? 5,
            tileProvider: $tileProvider ?? 'openstreetmap',
            class: $class,
            style: $style ?? 'height: 600px; width: 100%; border-radius: 0.5rem;',
            plugins: array_merge(['fullscreen'], $plugins),
            options: $options,
        );
    }

    #[On('leafwire:mapClicked')]
    public function onMapClicked(string $mapId, float $lat, float $lng): void
    {
        if ($mapId !== $this->mapId) {
            return;
        }

        $this->markerCount++;
        $id = "marker-{$this->markerCount}";

        $this->addMarker($id, $lat, $lng, [
            'popup' => "<strong>Marker #{$this->markerCount}</strong><br>Lat: ".round($lat, 5).'<br>Lng: '.round($lng, 5),
            'draggable' => true,
        ]);

        $this->selectedLocation = [
            'lat' => round($lat, 5),
            'lng' => round($lng, 5),
        ];
    }

    #[On('leafwire:markerDragged')]
    public function onMarkerDragged(string $mapId, string $markerId, float $lat, float $lng): void
    {
        if ($mapId !== $this->mapId) {
            return;
        }

        parent::onMarkerDragged($mapId, $markerId, $lat, $lng);

        $this->selectedLocation = [
            'lat' => round($lat, 5),
            'lng' => round($lng, 5),
        ];
    }

    public function clearAllMarkers(): void
    {
        $this->clearMarkers();
        $this->markerCount = 0;
        $this->selectedLocation = null;
    }

    public function switchTiles(string $provider): void
    {
        $this->setTileProvider($provider);
    }

    public function render()
    {
        return view('livewire.map-demo');
    }
}
