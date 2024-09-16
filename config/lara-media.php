<?php

return [
    //default database connection
    'connection' => env('LARA_MEDIA_CONNECTION', 'mongodb'),

    //default model store media information
    'model' => \Nhattuanbl\LaraMedia\Models\LaraMedia::class,

    //default db table name
    'table_name' => 'media',

    //dashboard
    'web' => [
        'enabled' => true,
        'prefix' => 'lara-media',
        'middleware' => ['web'],
    ],

    'public_assets' => [
        'root' => env('LARA_MEDIA_PUBLIC_ROOT', 'app'), //should be change in .env file
        'url' => env('LARA_MEDIA_PUBLIC_URL', '/assets/media'), //should change in .env file
    ],

    'temporary' => [
        'enabled' => true,
        'route_path' => 'download',
        'route_name' => 'web.download',
    ],

    //default storage disk
    'disk' => env('LARA_MEDIA_DISK', 'local'),

    //format date path - null for id / default: 'Y/m/d'
    'store_path' => 'Y/m/d',

    'conversion' => [
        'connection' => env('LARA_MEDIA_QUEUE_CONNECTION', 'mongodb'),

        'queue' => env('LARA_MEDIA_QUEUE_NAME', 'conversion'),

        //temporary directory
        'temp' => storage_path('temp'),

        //delete original file after convert
        'keep_original' => env('LARA_MEDIA_KEEP_ORIGINAL', true),

        //number of threads to use
        'threads' => env('LARA_MEDIA_THREAD', 16),

        //ffmpeg binary path
        'ffmpeg_path' => env('LARA_MEDIA_FFMPEG_PATH', '/usr/bin/ffmpeg'),
        'ffprobe_path' => env('LARA_MEDIA_FFPROBE_PATH', '/usr/bin/ffprobe'),
    ],

    'watermark' => [
        'path' => null, //storage_path('your watermark image.png')
        'position' => \Nhattuanbl\LaraMedia\Enums\PositionEnum::Center,
        'opacity' => 0.2,
        'height_percent' => 50,
    ],

    'photo' => [
        //get main color store in hex format
        'detect_main_color' => true,
    ],

    'video' => [
        //better quality at lower sizes
        '2pass_encoding' => false,

        //max timout for conversion
        'timeout' => 7200,

        'encoding' => [
            'mp4' => ['libx264', 'aac'],
            'webm' => ['libvpx', 'libvorbis'],
            'ts' => ['libx264', 'aac'],
            '3gp'  => ['libx264', 'aac'],
            '3g2'  => ['libx264', 'aac'],
            'mov'  => ['libx264', 'aac'],
            'fli'  => ['libx264', 'aac'],
            'mkv'  => ['libx264', 'aac'],
            'asf'  => ['wmv2', 'wmav2'],
            'wmv'  => ['wmv2', 'wmav2'],
        ],
    ],

    'audio' => [
        'timeout' => 7200,
        'encoding' => [
            'aac'  => 'aac', //required: yasm pkg-config libfdk-aac-dev
            'm4a'  => 'aac',
            'mp3'  => 'libmp3lame',
            'flac' => 'flac',
            'wav'  => 'pcm_s16le',
        ],
    ],
];
