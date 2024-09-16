<?php

namespace Nhattuanbl\LaraMedia\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\SerializesModels;
use Nhattuanbl\LaraMedia\Models\LaraMedia;

class MediaDeleted implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(#[WithoutRelations] public LaraMedia $media, public string|int|null $version = null)
    {
        $this->queue = config('lara-media.conversion.queue_name');
        $this->connection = config('lara-media.conversion.queue_connection');
    }
}
