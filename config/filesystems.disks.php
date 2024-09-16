<?php

return [
    'lara-media' => [
        'driver' => 'local',
        'root' => storage_path(env('LARA_MEDIA_PUBLIC_ROOT', 'app')),
        'url' => env('APP_URL') . env('LARA_MEDIA_PUBLIC_URL', '/assets/media'),
        'throw' => false,
    ],
];
