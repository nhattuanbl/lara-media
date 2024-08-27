<?php

namespace Nhattuanbl\LaraMedia\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Nhattuanbl\LaraMedia\Services\LaraMediaService;

class LaraMediaColorDetectJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public LaraMedia $media, public LaraMediaService $service)
    {
        //
    }

    /**
     * Execute the job.
     * @throws \ImagickException
     * @throws \Exception
     */
    public function handle(): void
    {
        if ($this->media->is_removed) {
            throw new \Exception('Media '.$this->media->id.' is removed');
        }

        $image = new Imagick();
        $image->readImageBlob(
            Storage::disk($this->media->disk)->get($this->media->path . '/' . $this->media->name . '.' . $this->media->ext)
        );

        $image->resizeImage(1, 1, Imagick::FILTER_LANCZOS, 1);
        $image->quantizeImage(1, Imagick::COLORSPACE_RGB, 0, false, false);
        $dominantColor = $image->getImageHistogram();
        $dominantColor = reset($dominantColor);
        $pixel = $dominantColor->getColor();
        $color = sprintf("#%02x%02x%02x", $pixel['r'], $pixel['g'], $pixel['b']);

        $properties = $this->media->properties;
        $properties['dominant_color'] = $color;
        $this->media->properties = $properties;
        $this->media->save();

        $image->clear();
        $image->destroy();
    }
}
