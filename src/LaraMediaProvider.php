<?php

namespace Nhattuanbl\LaraMedia;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Livewire;
use Mimey\MimeTypes;
use Nhattuanbl\LaraMedia\Contracts\MediaRegister;
use Nhattuanbl\LaraMedia\Livewire\AlbumReport;
use Nhattuanbl\LaraMedia\Livewire\ASide;
use Nhattuanbl\LaraMedia\Livewire\MediaTable;
use Nhattuanbl\LaraMedia\Livewire\Sidebar;
use Nhattuanbl\LaraMedia\Livewire\StorageReport;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Route;

class LaraMediaProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/lara-media.php' => config_path('lara-media.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'migration');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/lara-media'),
            ], 'assets');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/lara-media'),
            ], 'views');

        }

//        $loader = AliasLoader::getInstance();
        if (config('lara-media.web.enabled', false) === true) {
            $this->bootDashboard();
        }

        if (config('lara-media.temporary.enabled', false) === true) {
            $this->bootTemporaryDownload();
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lara-media');
        $this->loadViewComponentsAs('lara-media', [
            Sidebar::class,
            AlbumReport::class,
            StorageReport::class,
            MediaTable::class,
            ASide::class,
        ]);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lara-media.php', 'lara-media');
        $this->mergeConfigFrom(__DIR__.'/../config/logging.php', 'logging.channels');
        $this->mergeConfigFrom(__DIR__.'/../config/filesystems.disks.php', 'filesystems.disks');
        $this->mergeConfigFrom(__DIR__.'/../config/filesystems.links.php', 'filesystems.links');

        Livewire::component('lara-media::side-bar', Sidebar::class);
        Livewire::component('lara-media::album-report', AlbumReport::class);
        Livewire::component('lara-media::storage-report', StorageReport::class);
        Livewire::component('lara-media::media-table', MediaTable::class);
        Livewire::component('lara-media::a-side', ASide::class);

        $this->app->register(EventServiceProvider::class);
    }

    protected function bootDashboard(): void
    {
        Route::group([
            'domain' => config('lara-media.web.domain', null),
            'prefix' => config('lara-media.web.prefix'),
            'middleware' => config('lara-media.web.middleware'),
            'as' => 'lara-media.',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function bootTemporaryDownload(): void
    {
        Route::get(config('lara-media.temporary.route_path'), function(Request $request) {
            $sign = $request->query('signature');
            /** @var false|array $ar */
            $ar = LaraMedia::validTemporaryUrl($sign);
            if ($ar === false) {
                abort(410);
            }

            $media = LaraMedia::findOrFail($ar['id']);
            if ($ar['version']) {
                $path = $media->getVersionPath($ar['version']);
                $disk = $media->properties['conversion_disk'];
                $size = isset($media->conversions[$ar['version']]) ? $media->conversions[$ar['version']]['size'] : $media->responsive[$ar['version']]['size'];
                $ext = $media->is_audio ? $ar['version'] : $media->properties['ext'];
                $mime = (new MimeTypes)->getExtension($ext);
            } else {
                $path = $media->path . DIRECTORY_SEPARATOR . $media->name . '.' . $media->ext;
                $disk = $media->disk;
                $size = $media->size;
                $mime = $media->mime_type;
                if ($media->is_removed) {
                    abort(410);
                }
            }

            $stream = \Storage::disk($disk)->readStream($path);
            $response = response()->stream(function() use ($stream) {
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$media->properties['name'].'.'.$media->ext.'"',
                'Content-Length' => $size,
                'Accept-Ranges' => 'bytes',
            ]);

            if ($request->headers->has('Range')) {
                $range = $request->header('Range');
                if (preg_match('/bytes=(\d+)-(\d+)?/', $range, $matches)) {
                    $start = intval($matches[1]);
                    $end = isset($matches[2]) ? intval($matches[2]) : ($size - 1);
                    $response->setStatusCode(206);
                    $response->headers->set('Content-Range', "bytes $start-$end/$size");
                    $response->headers->set('Content-Length', ($end - $start) + 1);
                    fseek($stream, $start);
                }
            }

            return $response;
        })->name(config('lara-media.temporary.route_name'));
    }
}
