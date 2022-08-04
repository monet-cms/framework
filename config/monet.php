<?php

return [
    'settings' => [
        'driver' => 'file',

        'cache' => [
            'enabled' => true,
            'key' => env('MONET_SETTINGS_CACHE_KEY', 'monet.settings'),
            'ttl' => -1,
        ],

        'file' => [
            'path' => 'settings.json',
        ],

        'database' => [
            'table' => 'settings',

            'columns' => [
                'key' => env('MONET_SETTINGS_DATABASE_KEY_COLUMNS', 'key'),
                'value' => env('MONET_SETTINGS_DATABASE_VALUE_COLUMNS', 'value'),
            ],
        ],
    ],

    'themes' => [
        'paths' => [
            env('MONET_THEMES_PATH', base_path('themes')),
        ],

        'cache' => [
            'enabled' => true,
            'key' => env('MONET_THEMES_CACHE_KEY', 'monet.themes'),
        ],
    ],

    'modules' => [
        'paths' => [
            env('MONET_MODULES_PATH', base_path('modules')),
        ],

        'cache' => [
            'enabled' => true,
            'keys' => [
                'all' => env('MONET_ALL_MODULES_CACHE_KEY', 'monet.modules.all'),
                'ordered' => env('MONET_ORDERED_MODULES_CACHE_KEY', 'monet.modules.ordered')
            ],
        ],
    ],
];
