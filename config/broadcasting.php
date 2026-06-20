<?php
return [
    'default' => env('BROADCAST_CONNECTION', 'reverb'),

    'connections' => [
        'reverb' => [
            'driver'   => 'reverb',
            'key'      => env('REVERB_APP_KEY'),
            'secret'   => env('REVERB_APP_SECRET'),
            'app_id'   => env('REVERB_APP_ID'),
            'options'  => [
                // Le serveur PHP contacte Reverb en interne (même conteneur),
                // pas via l'URL publique, pour éviter un aller-retour réseau lent.
                'host'   => env('REVERB_SERVER_HOST', '127.0.0.1'),
                'port'   => env('REVERB_SERVER_PORT', 6001),
                'scheme' => env('REVERB_SERVER_SCHEME', 'http'),
                'useTLS' => env('REVERB_SERVER_SCHEME', 'http') === 'https',
            ],
            'client_options' => [],
        ],

        'log'  => ['driver' => 'log'],
        'null' => ['driver' => 'null'],
    ],
];