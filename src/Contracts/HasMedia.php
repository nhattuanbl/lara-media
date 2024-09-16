<?php

namespace Nhattuanbl\LaraMedia\Contracts;

use Exception;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Client\ConnectionException;
use Nhattuanbl\LaraMedia\Enums\FileTypeEnum;
use Nhattuanbl\LaraMedia\Enums\ResolutionEnum;
use Nhattuanbl\LaraMedia\Exceptions\DownloadFailedException;
use Nhattuanbl\LaraMedia\Exceptions\MimeTypeNotAllowedException;
use Nhattuanbl\LaraMedia\Exceptions\ReadFailedException;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Illuminate\Http\UploadedFile;

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

    public function getFirstMedia(?string $album = null): ?LaraMedia
    {
        if ($album) {
            return $this->media()->where('album', $album)->first();
        }

        return $this->media()->whereNull('album')->first();
    }

    public function registerMediaAlbum(): MediaRegister
    {
        return $this->addMediaAlbum();
    }

    protected function addMediaAlbum(?string $album = null): MediaRegister
    {
        $mediaRegister = new MediaRegister();
        if ($album) {
            $mediaRegister->album($album);
        }

        return $mediaRegister;
    }

    public function registerPhotoAlbum(): PhotoRegister
    {
        return $this->addPhotoAlbum();
    }

    protected function addPhotoAlbum(int|array $responsive = []): PhotoRegister
    {
        $mediaRegister = new PhotoRegister();
        $mediaRegister->responsive = $responsive;
        return $mediaRegister;
    }

    public function registerVideoAlbum(): VideoRegister
    {
        return $this->addVideoAlbum();
    }

    protected function addVideoAlbum(ResolutionEnum|array $responsive = []): VideoRegister
    {
        $mediaRegister = new VideoRegister();
        $mediaRegister->responsive($responsive);
        return $mediaRegister;
    }

    public function registerAudioAlbum(): AudioRegister
    {
        return $this->addAudioAlbum();
    }

    protected function addAudioAlbum(string|array $responsive = []): AudioRegister
    {
        $mediaRegister = new AudioRegister();
        $mediaRegister->responsive($responsive);
        return $mediaRegister;
    }

    /**
     * @throws DownloadFailedException
     * @throws ReadFailedException
     * @throws ConnectionException
     * @throws MimeTypeNotAllowedException
     * @throws Exception
     */
    public function addMedia(UploadedFile|LaraMedia|string $file): MediaRegister|PhotoRegister|VideoRegister|AudioRegister
    {
        $mainRegister = $this->registerMediaAlbum();
        $fileAttr = FileAttr::upload($file);

        if ($fileAttr->fileType == FileTypeEnum::Video) {
            $mediaRegister = $this->registerVideoAlbum();
            $mediaRegister->setConfig($mainRegister);
        } else if ($fileAttr->fileType == FileTypeEnum::Audio) {
            $mediaRegister = $this->registerAudioAlbum();
            $mediaRegister->setConfig($mainRegister);
        } else if ($fileAttr->fileType == FileTypeEnum::Image) {
            $mediaRegister = $this->registerPhotoAlbum();
            $mediaRegister->setConfig($mainRegister);
        } else {
            $mediaRegister = $mainRegister;
        }

        $allowed = false;
        foreach ($mediaRegister->acceptsMimeTypes as $pattern) {
            $pattern = preg_quote($pattern, '/');
            $pattern = str_replace('\*', '.*', $pattern);
            $pattern = '/^' . $pattern . '$/';
            if (preg_match($pattern, $fileAttr->mime) === 1) {
                $allowed = true;
                break;
            }
        }

        if ($allowed === false) {
            throw new MimeTypeNotAllowedException($fileAttr->mime . ' not allowed. ' . implode(', ', $mediaRegister->acceptsMimeTypes));
        }

        $mediaRegister->setFile($fileAttr);
        $mediaRegister->setModel($this);
        return $mediaRegister;
    }
}
