<?php

return [
    //default database connection
    'connection' => env('LARA_MEDIA_CONNECTION', 'mongodb'),

    //default model store media information
    'model' => \Nhattuanbl\LaraMedia\Models\LaraMedia::class,

    //default db table name
    'table_name' => 'media',

    //default storage disk
    'disk' => env('LARA_MEDIA_DISK', env('FILESYSTEM_DISK', 'local')),

    //generate unique file name when upload
    'unique_name' => true,

    //format date path / null for id / default: 'Y/m/d'
    'store_path' => 'Y/m/d',

    // add ?v=timestamp to url
    'url_version' => true,

    //temporary directory name in storage_path for conversion
    'temp' => 'temp',

    'conversion' => [
        'queue_connection' => env('LARA_MEDIA_QUEUE_CONNECTION', 'mongodb'),

        'queue_name' => env('LARA_MEDIA_QUEUE_NAME', 'conversion'),

        //delete original file after conversion
        'delete_original' => env('LARA_MEDIA_DELETE_ORIGINAL', false),

        //number of threads to use
        'threads' => env('LARA_MEDIA_THREAD', 16),
    ],

    'photo' => [
        //get main color store in hex format
        'detect_main_color' => false,
    ],

    'video' => [
        //ffmpeg binary path
        'ffmpeg_path' => env('LARA_MEDIA_FFMPEG_PATH', '/usr/bin/ffmpeg'),
        'ffprobe_path' => env('LARA_MEDIA_FFPROBE_PATH', '/usr/bin/ffprobe'),

        //better quality at lower sizes
        '2pass_encoding' => false,

        //max timout for conversion
        'timeout' => 7200
    ],

    'web' => [
        'prefix' => 'lara-media',
        'middleware' => ['web'],
    ],
];
