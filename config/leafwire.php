<?php

// config for ArbmanX/Leafwire

return [
    /*
    |--------------------------------------------------------------------------
    | Default Map Settings
    |--------------------------------------------------------------------------
    |
    | These settings are used when creating a new map instance without
    | explicitly specifying these values.
    |
    */
    'defaults' => [
        'center' => [51.505, -0.09],  // [latitude, longitude]
        'zoom' => 13,
        'min_zoom' => 1,
        'max_zoom' => 19,
        'scroll_wheel_zoom' => true,
        'dragging' => true,
        'touch_zoom' => true,
        'double_click_zoom' => true,
        'box_zoom' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tile Providers
    |--------------------------------------------------------------------------
    |
    | Configure tile providers for your maps. The 'default' key specifies
    | which provider to use when none is specified.
    |
    */
    'tiles' => [
        'default' => 'openstreetmap',

        'providers' => [
            'openstreetmap' => [
                'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                'subdomains' => 'abc',
                'max_zoom' => 19,
            ],

            'cartodb_positron' => [
                'url' => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                'subdomains' => 'abcd',
                'max_zoom' => 20,
            ],

            'cartodb_dark' => [
                'url' => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                'subdomains' => 'abcd',
                'max_zoom' => 20,
            ],

            'cartodb_voyager' => [
                'url' => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                'subdomains' => 'abcd',
                'max_zoom' => 20,
            ],

            'stadia_alidade' => [
                'url' => 'https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png',
                'attribution' => '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>, &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors',
                'max_zoom' => 20,
            ],

            'stadia_dark' => [
                'url' => 'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png',
                'attribution' => '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>, &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors',
                'max_zoom' => 20,
            ],

            'esri_world_street' => [
                'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
                'attribution' => 'Tiles &copy; Esri',
                'max_zoom' => 19,
            ],

            'esri_world_imagery' => [
                'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                'attribution' => 'Tiles &copy; Esri',
                'max_zoom' => 19,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Configuration
    |--------------------------------------------------------------------------
    |
    | Plugins listed here are auto-loaded when creating maps. Set to true
    | for default configuration, or provide an array for custom settings.
    |
    | Available bundled plugins:
    | - marker-cluster: Groups nearby markers into clusters
    | - fullscreen: Adds fullscreen control button
    | - locate: Adds locate/geolocation control
    | - draw: Drawing tools for shapes and markers
    | - geosearch: Address/location search
    | - minimap: Shows a minimap overview
    | - measure: Distance and area measurement
    | - heatmap: Heatmap visualization
    |
    */
    'plugins' => [
        // 'marker-cluster' => true,
        // 'fullscreen' => true,
        // 'locate' => [
        //     'position' => 'topright',
        //     'fly_to' => true,
        //     'keep_current_zoom_level' => false,
        // ],
        // 'draw' => [
        //     'position' => 'topright',
        // ],
        // 'geosearch' => [
        //     'provider' => 'openstreetmap',
        //     'style' => 'bar',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how Leaflet and plugin assets are loaded.
    |
    */
    'assets' => [
        'leaflet_version' => '1.9.4',

        // Use CDN for assets (recommended for simplicity)
        'cdn' => true,

        // If cdn is false, assets will be loaded from this path
        'local_path' => '/vendor/leafwire',
    ],

    /*
    |--------------------------------------------------------------------------
    | Map Container Defaults
    |--------------------------------------------------------------------------
    |
    | Default styling for the map container element.
    |
    */
    'container' => [
        'class' => 'leafwire-map-container',
        'style' => 'height: 400px; width: 100%;',
    ],

    /*
    |--------------------------------------------------------------------------
    | Marker Defaults
    |--------------------------------------------------------------------------
    |
    | Default settings for markers.
    |
    */
    'markers' => [
        'draggable' => false,
        'keyboard' => true,
        'rise_on_hover' => false,
        'icon' => null, // Use Leaflet default icon
    ],

    /*
    |--------------------------------------------------------------------------
    | GeoJSON Defaults
    |--------------------------------------------------------------------------
    |
    | Default styling for GeoJSON layers.
    |
    */
    'geojson' => [
        'style' => [
            'color' => '#3388ff',
            'weight' => 3,
            'opacity' => 1,
            'fill_color' => '#3388ff',
            'fill_opacity' => 0.2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Controls
    |--------------------------------------------------------------------------
    |
    | Configure which controls are shown by default.
    |
    */
    'controls' => [
        'zoom' => [
            'enabled' => true,
            'position' => 'topleft',
        ],
        'attribution' => [
            'enabled' => true,
            'position' => 'bottomright',
            'prefix' => 'Leafwire',
        ],
        'scale' => [
            'enabled' => false,
            'position' => 'bottomleft',
            'metric' => true,
            'imperial' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Configure which map events should be dispatched to Livewire.
    |
    */
    'events' => [
        'click' => true,
        'dblclick' => false,
        'contextmenu' => false,
        'moveend' => true,
        'zoomend' => true,
        'load' => true,
    ],
];
