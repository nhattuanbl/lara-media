<?php

namespace Nhattuanbl\LaraMedia;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Nhattuanbl\LaraMedia\Events\MediaDeleted;
use Nhattuanbl\LaraMedia\Listeners\DeleteMediaVersions;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MediaDeleted::class => [
            DeleteMediaVersions::class,
        ]
    ];

    public function boot()
    {
        parent::boot();
    }
}
