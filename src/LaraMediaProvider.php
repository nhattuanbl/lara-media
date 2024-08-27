<?php

namespace Nhattuanbl\LaraMedia;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Livewire;
use Nhattuanbl\LaraMedia\Livewire\AlbumReport;
use Nhattuanbl\LaraMedia\Livewire\ASide;
use Nhattuanbl\LaraMedia\Livewire\MediaTable;
use Nhattuanbl\LaraMedia\Livewire\Sidebar;
use Nhattuanbl\LaraMedia\Livewire\StorageReport;
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

        Route::group([
            'prefix' => config('lara-media.web.prefix'),
            'middleware' => config('lara-media.web.middleware'),
            'as' => 'lara-media.',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

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

        Livewire::component('lara-media::side-bar', Sidebar::class);
        Livewire::component('lara-media::album-report', AlbumReport::class);
        Livewire::component('lara-media::storage-report', StorageReport::class);
        Livewire::component('lara-media::media-table', MediaTable::class);
        Livewire::component('lara-media::a-side', ASide::class);
    }
}
