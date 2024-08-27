<?php

return [
    'lara-media' => [
        'driver' => 'daily',
        'path' => storage_path('logs/lara-media.log'),
        'level' => 'debug',
        'days' => 30,
        'replace_placeholders' => true,
    ]
];
