<?php

namespace Nhattuanbl\LaraMedia\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickException;
use Imtigger\LaravelJobStatus\Trackable;
use League\Flysystem\FilesystemException;
use Nhattuanbl\LaraMedia\Contracts\PhotoRegister;
use Nhattuanbl\LaraMedia\Enums\PositionEnum;
use Nhattuanbl\LaraMedia\Models\LaraMedia;

class PhotoConvertJob implements ShouldQueue
{
    use Queueable, Trackable;

    public function __construct(#[WithoutRelations] public LaraMedia $media, public PhotoRegister $config)
    {
        $this->afterCommit();
        $this->prepareStatus([
            'model_type' => get_class($media),
            'model_id' => $media->id,
//            'status' => 'queued',
//            'queue' => $this->config->queue,
        ]);
    }

    /**
     * @throws ImagickException
     * @throws FilesystemException
     * @throws Exception
     */
    public function handle(): void
    {
        if ($this->media->is_removed) {
            throw new Exception('Media '.$this->media->id.' is removed');
        }

        $this->config->responsive = array_unique($this->config->responsive);
        $this->setProgressMax(count($this->config->responsive));

        if (config('lara-media.photo.detect_main_color', false) === true) {
            dispatch_sync(new ColorDetectJob($this->media, $this->config));
        }

        usort($this->config->responsive, fn($a, $b) => $a <=> $b );
        $responsive = $this->media->responsive;
        $total_took = 0;

        foreach ($this->config->responsive as $width) {
            $this->incrementProgress();
            $took = microtime(true);

            $image = new Imagick();
            $image->readImageBlob(
                Storage::disk($this->media->disk)->get($this->media->path . '/' . $this->media->name . '.' . $this->media->ext)
            );

            if ($this->config->quality) {
                $image->setImageCompressionQuality($this->config->quality);
            }
            $image->setImageFormat($this->config->format);
            $image->stripImage();
            $image->thumbnailImage($width, 0);
            $image = $this->watermark($image);

            Storage::disk($this->config->conversionDisk)->write(
                $this->media->path . '/' . $this->media->name . '_converted_' . $width . '.' . $this->config->format,
                $image->getImageBlob()
            );

            $filesize = $image->getImageLength();
            $image->clear();
            $image->destroy();

            $responsive[$width] = [
                'size' => $filesize,
                'took' => number_format(microtime(true) - $took, 2, '.', ''),
            ];

            $total_took += $responsive[$width]['took'];
            $this->media->total_files++;
            $this->media->total_size += $filesize;
        }

        $this->media->responsive = $responsive;
        $properties = $this->media->properties;
        $properties['conversion_disk'] = $this->config->conversionDisk;
        $properties['took'] = (float) number_format($total_took, 2);
        $properties['ext'] = $this->config->format;
        $this->media->properties = $properties;

        if ($this->config->keepOrigin === false) {
            Storage::disk($this->media->disk)->delete($this->media->path . '/' . $this->media->name . '.' . $this->media->ext);
            $this->media->is_removed = true;
            $this->media->total_files--;
        }

        $this->media->save();
        $this->setOutput([]);
    }

    /**
     * @throws ImagickException
     */
    protected function watermark(Imagick $image): Imagick
    {
        if (!$this->config->watermarkPath) {
            return $image;
        }

        $watermark = new Imagick();
        $watermark->readImage($this->config->watermarkPath);

        $imageWidth = $image->getImageWidth();
        $imageHeight = $image->getImageHeight();

        $watermarkHeight = ($this->config->watermarkHeight / 100) * $imageHeight;
        $watermarkWidth = ($watermark->getImageWidth() / $watermark->getImageHeight()) * $watermarkHeight;
        $watermark->resizeImage($watermarkWidth, $watermarkHeight, Imagick::FILTER_LANCZOS, 1);

        $watermarkOpacity = clone $watermark;
        $watermarkOpacity->evaluateImage(Imagick::EVALUATE_MULTIPLY, $this->config->watermarkOpacity, Imagick::CHANNEL_ALPHA);

        if ($this->config->watermarkPosition == PositionEnum::TopLeft) {
            $x = 0;
            $y = 0;
        } elseif ($this->config->watermarkPosition == PositionEnum::TopRight) {
            $x = $imageWidth - $watermarkWidth;
            $y = 0;
        } elseif ($this->config->watermarkPosition == PositionEnum::BottomLeft) {
            $x = 0;
            $y = $imageHeight - $watermarkHeight;
        } elseif ($this->config->watermarkPosition == PositionEnum::BottomRight) {
            $x = $imageWidth - $watermarkWidth;
            $y = $imageHeight - $watermarkHeight;
        } else {
            $x = ($imageWidth - $watermarkWidth) / 2;
            $y = ($imageHeight - $watermarkHeight) / 2;
        }

        $image->compositeImage($watermarkOpacity, Imagick::COMPOSITE_OVER, $x, $y);

        $watermark->destroy();
        $watermarkOpacity->destroy();

        return $image;
    }
}
