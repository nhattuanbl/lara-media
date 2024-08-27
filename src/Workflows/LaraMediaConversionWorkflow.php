<?php

namespace Nhattuanbl\LaraMedia\Workflows;

use Nhattuanbl\LaraMedia\LaraMedia;
use Nhattuanbl\LaraMedia\LaraMediaService;
use Workflow\ActivityStub;
use Workflow\Workflow;

class LaraMediaConversionWorkflow extends Workflow
{
    public int $tries = 1;

    public function execute(LaraMedia $media, LaraMediaService $videoService): \Generator
    {
        return yield ActivityStub::make(LaraMediaConversionActivity::class, $media, $videoService);
    }
}
