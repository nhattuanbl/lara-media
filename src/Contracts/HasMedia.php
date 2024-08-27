<?php

namespace Nhattuanbl\LaraMedia\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MongoDB\Laravel\Eloquent\Model;
use Nhattuanbl\LaraHelper\Helpers\StringHelper;
use Nhattuanbl\LaraMedia\Enums\ResolutionEnum;
use Nhattuanbl\LaraMedia\Exceptions\FileExistsException;
use Nhattuanbl\LaraMedia\Exceptions\MimeTypeNotAllowedException;
use Nhattuanbl\LaraMedia\Exceptions\ReadFailedException;
use Nhattuanbl\LaraMedia\Jobs\LaraMediaColorDetectJob;
use Nhattuanbl\LaraMedia\Jobs\LaraMediaConversionJob;
use Nhattuanbl\LaraMedia\Jobs\LaraMediaResponsiveJob;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Nhattuanbl\LaraMedia\Services\LaraMediaService;

/**
 * @mixin \Illuminate\Database\Eloquent\Model|Model
 */
trait HasMedia
{
    use DispatchesJobs;

    protected static function bootHasMedia(): void
    {
        static::deleting(function ($model) {
            $model->media()->delete();
        });
    }

    public function media(): MorphMany
    {
        return $this->morphMany(config('lara-media.model'), 'model');
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function addMedia(UploadedFile|LaraMedia|string $fileOrUrl, bool $withoutResponsive = false, bool $withoutConversion = false): void
    {
        $fileAttr = LaraMediaService::getFileAttr($fileOrUrl);
        $laraMedia = $this->registerMediaAlbum();

        if (!empty($laraMedia->acceptsMimeTypes) && !in_array($fileAttr->mime, $laraMedia->acceptsMimeTypes)) {
            throw new MimeTypeNotAllowedException($fileAttr->mime);
        }

        $media = $this->media()->create([
            'album' => $laraMedia->album,
            'disk' => $laraMedia->disk,
            'path' => null,
            'name' => config('lara-media.unique_name') ? StringHelper::randLow() : LaraMediaService::sanitizeFilename($fileAttr->name),
            'ext' => $fileAttr->extension,
            'mime_type' => $fileAttr->mime,
            'size' => $fileAttr->size,
            'properties' => config('lara-media.unique_name') ? ['origin_name' => $fileAttr->name] : [],
            'conversions' => [],
            'responsive' => [],
            'is_removed' => false,
        ]);

        $datePath = config('lara-media.store_path');
        $path = $datePath ? date($datePath) : $media->id;
        if ($datePath && Storage::disk($laraMedia->disk)->exists($path . '/' . $media->name . '.' . $media->ext)) {
            throw new FileExistsException($path . '/' . $media->name . '.' . $media->ext);
        }

        if ($fileOrUrl instanceof UploadedFile) {
            $fileOrUrl->storeAs($path, $media->name . '.' . $media->ext, $laraMedia->disk);
        } else if ($fileOrUrl instanceof LaraMedia) {
            $resource = Storage::disk($fileOrUrl->disk)->readStream($fileOrUrl->path . '/' . $fileOrUrl->name . '.' . $fileOrUrl->ext);
            if (!$resource) {
                $media->delete();
                throw new ReadFailedException('['.$fileOrUrl->disk.'] ' . $fileOrUrl->path . '/' . $fileOrUrl->name . '.' . $fileOrUrl->ext);
            }

            Storage::disk($laraMedia->disk)->writeStream($path . '/' . $media->name . '.' . $media->ext, $resource);

            if ($fileOrUrl->is_temporary) {
                $fileOrUrl->delete();
            }
        } else {
            $fp = fopen($fileAttr->path, 'r');
            Storage::disk($laraMedia->disk)->writeStream($path . '/' . $media->name . '.' . $media->ext, $fp);
            fclose($fp);

            if ($fileAttr->isTemporary) {
                unlink($fileAttr->path);
            }
        }

        $media->total_files = 1;
        $media->total_size = $fileAttr->size;
        $media->hash = hash_file('md5', $fileAttr->path);
        $media->path = $path;
        $media->save();

        $oldMedia = $this->media()->select('id');
        if (is_null($laraMedia->album)) {
            $oldMedia->whereNull('album');
        } else {
            $oldMedia->where('album', $laraMedia->album);
        }
        $oldMedia = $oldMedia->orderBy('created_at')->get();

        if ($oldMedia->count() > $laraMedia->limit) {
            $oldMedia = $oldMedia->take($oldMedia->count() - $laraMedia->limit);
            \DB::transaction(function () use ($oldMedia, $laraMedia) {
                foreach ($oldMedia as $o) {
                    $o->delete();
                }
            });
        }

        if (!$withoutResponsive) {
            $photoService = $this->registerPhotoResponsive();
        }

        if (!$withoutConversion) {
            $videoService = $this->registerVideoConversion();
        }

        if (!empty($photoService->acceptsMimeTypes) &&
            config('lara-media.photo.detect_main_color') &&
            in_array($fileAttr->mime, $photoService->acceptsMimeTypes)
        ) {
            Log::channel('lara-media')->info('Init ColorDetectJob #' . $media->id);
            $colorJob = new LaraMediaColorDetectJob($media, $laraMedia);
        }

        if (!$withoutResponsive &&
            !empty($photoService->acceptsMimeTypes) &&
            in_array($fileAttr->mime, $photoService->acceptsMimeTypes) &&
            !empty($photoService->responsive)
        ) {
            Log::channel('lara-media')->info('Init ResponsiveJob #' . $media->id);
            $responsiveJob = new LaraMediaResponsiveJob($media, $photoService);
        } else if (!$withoutConversion &&
            !empty($videoService->acceptsMimeTypes) &&
            in_array($fileAttr->mime, $videoService->acceptsMimeTypes)
        ) {
            Log::channel('lara-media')->info('Init ConversionJob #' . $media->id);
//            LaraMediaConversionJob::dispatch($media, $videoService)->onConnection($videoService->queue_connection)->onQueue($videoService->queue_name);
//            WorkflowStubManager::make(LaraMediaConversionWorkflow::class, $videoService->queue_connection, $videoService->queue_name)->start($media, $videoService);

            $conversionJob = new LaraMediaConversionJob($media, $videoService);
            $this->dispatch($conversionJob->onConnection($videoService->queue_connection)->onQueue($videoService->queue_name));
        }

        if (isset($responsiveJob)) {
            $this->dispatch($responsiveJob->onConnection($photoService->queue_connection)->onQueue($photoService->queue_name));
        } else if (isset($colorJob)) {
            dispatch($colorJob->onConnection($laraMedia->queue_connection)->onQueue($laraMedia->queue_name));
        }
    }

    protected function addMediaAlbum(?string $album = null): LaraMediaService
    {
        $laraMedia = new LaraMediaService();
        if ($album) {
            $laraMedia->toAlbum($album);
        }
        return $laraMedia;
    }

    public function registerMediaAlbum(): LaraMediaService
    {
        return $this->addMediaAlbum();
    }

    protected function addPhotoResponsive(array|int $breakpoints): LaraMediaService
    {
        $laraMedia = new LaraMediaService();
        $laraMedia->responsive = is_int($breakpoints) ? [$breakpoints] : $breakpoints;
        $laraMedia->acceptsMimeTypes([
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/heic',
            'image/tiff',
            'image/x-dpx',
            'image/x-exr',
            'image/svg+xml',
        ]);
        return $laraMedia;
    }

    public function registerPhotoResponsive(): LaraMediaService
    {
        return new LaraMediaService();
    }

    protected function addVideoConversion(ResolutionEnum|array $resolution): LaraMediaService
    {
        $laraMedia = new LaraMediaService();
        $laraMedia->resolution = is_array($resolution) ? $resolution : [$resolution];
        $laraMedia->acceptsMimeTypes([
            'video/mp4',
            'video/webm',
            'video/x-msvideo',
            'video/quicktime',
            'video/x-matroska',
            'video/x-flv',
            'video/x-ms-wmv',
            'video/mpeg',
        ]);
        return $laraMedia;
    }

    public function registerVideoConversion(): LaraMediaService
    {
        return new LaraMediaService();
    }

    public function getFirstMedia(?string $album = null): ?LaraMedia
    {
        if ($album) {
            return $this->media()->where('album', $album)->first();
        }

        return $this->media()->whereNull('album')->first();
    }
}
