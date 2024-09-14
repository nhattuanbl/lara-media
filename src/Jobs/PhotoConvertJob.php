<?php

namespace Nhattuanbl\LaraMedia\Jobs;

use App\Models\JobStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Imtigger\LaravelJobStatus\Trackable;
use League\Flysystem\FilesystemException;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Nhattuanbl\LaraMedia\Services\LaraMediaService;

class ResponsiveJob implements ShouldQueue
{
    use Queueable, Trackable;

    public function __construct(
        #[WithoutRelations] public LaraMedia $media,
        public LaraMediaService $service
    )
    {
        $this->afterCommit();
        $this->prepareStatus(['model_type' => get_class($this->media), 'model_id' => $this->media->id]);
    }

    /**
     * @throws \ImagickException
     * @throws FilesystemException
     */
    public function handle(): void
    {
        if ($this->media->is_removed) {
            throw new \Exception('Media '.$this->media->id.' is removed');
        }

        $took = time();
        if (config('lara-media.photo.detect_main_color')) {
            dispatch_sync(new ColorDetectJob($this->media, $this->service));
        }

        usort($this->service->responsive, fn($a, $b) => $a <=> $b );
        $this->setProgressMax(count($this->service->responsive));
        foreach ($this->service->responsive as $s) {
            $this->incrementProgress();
            $this->setOutput(['msg' => 'Responsive size '.$s]);

            $image = new Imagick();
            $image->readImageBlob(
                Storage::disk($this->media->disk)->get($this->media->path . '/' . $this->media->name . '.' . $this->media->ext)
            );

            if ($this->service->format) {
                $image->setImageFormat($this->service->format);
            }

            $image->setImageCompressionQuality($this->service->quality);
            $image->stripImage();

            $image->thumbnailImage($s, 0);

            Storage::disk($this->service->disk)->write(
                $this->media->path . '/' . $this->media->name . '_' . $s . '.' . $this->media->ext,
                $image->getImageBlob()
            );

            $length = $image->getImageLength();
            $ext = $image->getImageFormat();
            $image->clear();
            $image->destroy();

            $responsive = $this->media->responsive;
            if (isset($responsive[$s])) {
                \Log::channel('lara-media')->warning('Responsive '.$s.' already exists for media #'.$this->media->id);
            }
            $responsive[$s] = ['size' => $length];
            $this->media->responsive = $responsive;
            $this->media->total_size += $length;
        }

        $properties = $this->media->properties;
        $properties['disk'] = $this->service->disk;
        $properties['took'] = time() - $took;
        $properties['ext'] = strtolower($this->service->format ?? $this->media->ext);
        $this->media->properties = $properties;

        if ($this->service->deleteOriginal) {
            Storage::disk($this->media->disk)->delete($this->media->path . '/' . $this->media->name . '.' . $this->media->ext);
            $this->media->total_files--;
            $this->media->is_removed = true;
        }

        $this->media->total_files += count($this->media->responsive);
        $this->media->save();
    }
}
