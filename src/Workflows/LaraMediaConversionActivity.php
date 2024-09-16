<?php

namespace Nhattuanbl\LaraMedia\Workflows;

use Illuminate\Support\Facades\Log;
use Nhattuanbl\LaraHelper\Helpers\FileHelper;
use Nhattuanbl\LaraMedia\Convert;
use Nhattuanbl\LaraMedia\LaraMedia;
use Nhattuanbl\LaraMedia\LaraMediaService;
use Workflow\Activity;

class LaraMediaConversionActivity extends Activity
{
    public $tries = 1;
    public $timeout = 7320;

    /**
     * @throws \Exception
     */
    public function execute(LaraMedia $media, LaraMediaService $videoService)
    {

    }
}
