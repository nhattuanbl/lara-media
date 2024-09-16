## Lara Media
Lara Media is a media library for Laravel. It provides a simple way to store, convert, and manipulate media files.

![Preview](https://i.imgur.com/POP8N0S.png "LaraMedia")

### Features
- Store media information in MongoDB (other connection is not tested)
- Multiple disk storage
- Multiple album/collection for each model
- Convert media files to other formats / other resolution
- Detect main color of photo
- Add watermark to photo
- Add watermark to video
- Get thumbnail of media
- Get multiple screenshot of video
- Get multiple resolution of photo
- Dashboard to manage media files, view statistic, tracking conversion status via queue
- Url version
- Temporary url

### Requirements
- php >= 8.2
- laravel >= 9.18
- ext-mongodb
- ext-imagick
- [imtigger/laravel-job-status](https://github.com/imTigger/laravel-job-status)
- [ffmpeg](https://www.ffmpeg.org/)

### Installation
```bash
composer require nhattuanbl/lara-media
```

```bash
#publish config
php artisan vendor:publish --provider="Nhattuanbl\LaraMedia\Providers\LaraMediaServiceProvider" --tag="config"
#publish migration
php artisan vendor:publish --provider="Nhattuanbl\LaraMedia\Providers\LaraMediaServiceProvider" --tag="migration"
#publish view
php artisan vendor:publish --provider="Nhattuanbl\LaraMedia\Providers\LaraMediaServiceProvider" --tag="views"
#publish assets
php artisan vendor:publish --provider="Nhattuanbl\LaraMedia\Providers\LaraMediaServiceProvider" --tag="assets"
```

```bash
php artisan migrate
php artisan storage:link
```

### Config
```php
return [
    //default database connection - not tested on other connection
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
        'root' => env('LARA_MEDIA_PUBLIC_ROOT', 'app'), //should be set in .env file
        'url' => env('LARA_MEDIA_PUBLIC_URL', '/assets/media'), //should be set in .env file
    ],
    
    'temporary' => [
        'enabled' => true,
        'route_path' => 'download',
        'route_name' => 'web.download',
    ],

    //default storage disk
    'disk' => env('LARA_MEDIA_DISK', 'local'),

    //dir name - can be date path - null for id / default: 'Y/m/d'
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
```

### Usage
```php
//App\Models\User.php
use MongoDB\Laravel\Eloquent\HybridRelations;
use Nhattuanbl\LaraMedia\Contracts\AudioRegister;
use Nhattuanbl\LaraMedia\Contracts\HasMedia;
use Nhattuanbl\LaraMedia\Contracts\MediaRegister;
use Nhattuanbl\LaraMedia\Contracts\VideoRegister;
use Nhattuanbl\LaraMedia\Enums\PositionEnum;
use Nhattuanbl\LaraMedia\Enums\ResolutionEnum;

class User extends Model
{
    use HasMedia, HybridRelations;
    
    ...
    
    public function registerMediaAlbum(): MediaRegister
    {
        return $this->addMediaAlbum()
        ->acceptsMimeTypes(['image/*']) // use */* for all mime types
        ->album('defaultalbum') //default album name for all uploaded files
        ->limit(3); //global limit
    }

    public function registerVideoAlbum(): VideoRegister
    {
        return $this->addVideoAlbum([ResolutionEnum::H240, ResolutionEnum::H360])
            ->limit(5) // override registerMediaAlbum limit
            ->album('videoalbum') //override registerMediaAlbum album
            ->onQueue('default') //optional - override lara-media config
            ->onConnection('sync') //optional - override lara-media config
            ->format('mp4') //optional
            ->quality(55) //optional - quality video percent 0-100
            ->keepOrigin(false) //optional - keep original file after convert
            ->description('some desc') //optional - store video description
            ->watermark(
                storage_path('watermark.png'),
                 PositionEnum::BottomLeft, 
                 0.5, 
                 50
            ) //all optional - override lara-media config
            ->posters(
                5, //required - number of screenshot
                300, //optional - width of screenshot
            )
            ->thumbnail(4, 4) //optional - default 16 (row * col) images in thumbnail
            ->conversionDisk('s3') //optional - override lara-media config
            ->onDisk('local') //optional - override lara-media config
            ;
    }
    
    public function registerPhotoAlbum(): PhotoRegister
    {
        return $this->addPhotoAlbum()
            ->album('photoalbum')
            ->limit(3)
            ->responsive([600, 300])
            ->onQueue('default')
            ->onConnection('sync')
            ->watermark()
            ;
    }

    public function registerAudioAlbum(): AudioRegister
    {
        return $this->addAudioAlbum()
            ->album('audioalbum')
            ->limit(5)
            ->description('some desc')
            ->responsive(['wav', 'mp3'])
            ->conversionDisk('s3')
            ->onDisk('local')
            ->keepOrigin(false)
            ->quality(80)
            ;
    }
}
```

```php
//App\Http\Controllers\
use Nhattuanbl\LaraMedia\Models\LaraMedia;

    //add media from local file
    $model->addMedia(storage_path('photo.webp'))->toAlbum();
    
    //add media from url
    $model->addMedia('https://example.com/photo.webp')
        ->onDisk('override model register disk')
        ->toAlbum('override model register album name');
    
    //add media from base64
    $model->addMedia('data:image/png;base64,....')
        ->onQueue('override model register')
        ->toAlbum();
    
    //copy media from LaraMedia
    $model->addMedia(LaraMedia::findOrFail($id))
        ->withoutResponsive() //optional - disable conversion
        ->toAlbum();
    
    
    //get original media url
    LaraMedia::findOrFail($id)->url;
    
    //get media url with version
    LaraMedia::findOrFail($id)->getUrlAttribute('400');
    
    //get media temporary url
    LaraMedia::findOrFail($id)->temporaryUrl(
        now()->addHours(1)->timestamp, //expire time
        $version, //optional - null for original version
        ['views' => 1] //optional - allow accessible only 1 time
    );
```

### Custom uploaded file naming
```php
//App/Providers/AppServiceProvider.php
public function boot()
{
    MediaRegister::$sanitizeFilename = function (string $filename) {
        $invalidChars = [
            '\\', '/', ':', '*', '?', '"', '<', '>', '|',
            "\0", "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08",
            "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F", "\x10", "\x11", "\x12",
            "\x13", "\x14", "\x15", "\x16", "\x17", "\x18", "\x19", "\x1A", "\x1B", "\x1C",
            "\x1D", "\x1E", "\x1F"
        ];

        $sanitized = str_replace($invalidChars, '_', $filename);
        $sanitized = trim($sanitized, " \t\n\r\0\x0B.");
        return strlen($sanitized) ? $sanitized : floor(microtime(true) * 1000);
    };
}
```

### Custom media model
```php
//App/Models/CustomMedia.php
namespace App\Models;

class CustomMedia extends \Nhattuanbl\LaraMedia\Models\LaraMedia
{
    protected $table = 'custom_media';
    protected $connection = 'mysql';
    
    public function get_idAttribute(): int|string
    {
        return $this->id;
    }
}

//lara-media.php
    ...
    'model' => \App\Models\CustomMedia::class,
```

### Troubleshooting
In migration file you will see that im added 2 new column to customize imtigger/laravel-job-status package for tracking media conversion status so this migration file should run after imtigger/laravel-job-status migration file. Have fun!
