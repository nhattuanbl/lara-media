<?php

namespace Nhattuanbl\LaraMedia\Contracts;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Nhattuanbl\LaraMedia\Enums\FileTypeEnum;
use Nhattuanbl\LaraMedia\Exceptions\FileExistsException;
use Nhattuanbl\LaraMedia\Jobs\AudioConvertJob;
use Nhattuanbl\LaraMedia\Jobs\ColorDetectJob;
use Nhattuanbl\LaraMedia\Jobs\PhotoConvertJob;
use Nhattuanbl\LaraMedia\Jobs\VideoConvertJob;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Throwable;

class MediaRegister
{
    use DispatchesJobs;

    /** @var callable */
    public static $sanitizeFilename;

    public string $queue;
    public string $connection;
    public string $disk;
    public string $conversionDisk;
    public bool $keepOrigin;
    public ?int $limit = null;
    public ?string $album = null;
    public array $responsive = [];
    public ?string $description = null;

    public ?int $quality = null;
    public string $format;

    public array $acceptsMimeTypes = ['*/*'];

    public FileAttr $fileAttr;
    public Model $model;

    public function __construct()
    {
        $this->queue = config('lara-media.conversion.queue', 'default');
        $this->connection = config('lara-media.conversion.connection', 'sync');
        $this->disk = $this->conversionDisk = config('lara-media.disk', 'local');
        $this->keepOrigin = config('lara-media.conversion.keep_original', true);
    }
    public function setConfig(self $mediaRegister): void
    {
        $this->limit ??= $mediaRegister->limit;
        $this->album ??= $mediaRegister->album ;
    }

    public function onQueue(string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function onConnection(string $connection): self
    {
        if (!in_array($connection, array_keys(config('queue.connections')))) {
            throw new Exception('Connection not found');
        }

        $this->connection = $connection;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function onDisk(string $disk): self
    {
        if (!in_array($disk, array_keys(config('filesystems.disks')))) {
            throw new Exception('Disk not found');
        }

        $this->disk = $disk;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function conversionDisk(string $disk): self
    {
        if (!in_array($disk, array_keys(config('filesystems.disks')))) {
            throw new Exception('Disk not found');
        }

        $this->conversionDisk = $disk;
        return $this;
    }

    public function keepOrigin(bool $keep): self
    {
        $this->keepOrigin = $keep;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function acceptsMimeTypes(array $mimeType): self
    {
        $this->acceptsMimeTypes = $mimeType;
        return $this;
    }

    public function quality(int $percent): self
    {
        $this->quality = $percent;
        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    public function description(string $desc): self
    {
        $this->description = $desc;
        return $this;
    }

    public function withoutResponsive(): self
    {
        $this->responsive = [];
        return $this;
    }

    public function album(string $album): self
    {
        $this->album = $album;
        return $this;
    }

    public function setFile(FileAttr $fileAttr): self
    {
        $this->fileAttr = $fileAttr;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function setModel(Model $model): self
    {
        if (!in_array(HasMedia::class, class_uses_recursive(get_class($model)))) {
            throw new Exception('Model must use HasMedia trait');
        }
        $this->model = $model;
        return $this;
    }

    public function sanitizeFilename(callable $callback): self
    {
        self::$sanitizeFilename = $callback;
        return $this;
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function toAlbum(?string $album = null): LaraMedia
    {
        if (!isset($this->fileAttr) || !isset($this->model)) {
            throw new Exception('File or Model is not set');
        }

        $name = is_callable(self::$sanitizeFilename) ?
            call_user_func(self::$sanitizeFilename, $this->fileAttr->name) :
            strtolower(floor(microtime(true) * 1000));
        $name = trim($name);

        if (strlen($name) == 0) {
            throw new Exception('Filename invalid or empty');
        }

        /** @var LaraMedia $media */
        $media = $this->model->media()->create([
            'album' => $album ?? $this->album,
            'disk' => $this->disk,
            'path' => null,
            'name' => $name,
            'ext' => $this->fileAttr->extension,
            'mime_type' => $this->fileAttr->mime,
            'size' => $this->fileAttr->size,
            'properties' => ['name' => $this->fileAttr->name],
            'conversions' => [],
            'conversion_disk' => null,
            'responsive' => [],
            'is_removed' => false,
            'description' => $this->description,
        ]);

        try {
            $datePath = config('lara-media.store_path');
            $path = $datePath ? date($datePath) : $media->id;
            $fullPath = $path . '/' . $media->name . '.' . $media->ext;
            if (Storage::disk($this->disk)->exists($fullPath)) {
                throw new FileExistsException($fullPath);
            }

            $fileName = $this->fileAttr->tempName ?? ($this->fileAttr->name . '.' . $this->fileAttr->extension);
            if (FileAttr::isLocalDisk($this->disk) && $this->fileAttr->isTemporary) {
                rename($this->fileAttr->path . '/' . $fileName, Storage::disk($this->disk)->path($fullPath));
            } else {
                $fp = fopen($this->fileAttr->path . '/' . $fileName, 'r');
                Storage::disk($this->disk)->writeStream($fullPath, $fp);
                fclose($fp);
            }

            $media->total_files = 1;
            $media->total_size = $this->fileAttr->size;
            $media->hash = hash_file('md5', $this->fileAttr->path . '/' . $fileName);
            $media->path = $path;
            $media->save();
        } catch (Throwable $e) {
            $media->delete();
            throw $e;
        }

        if ($this->fileAttr->isTemporary) {
            if ($this->fileAttr->id) {
                $mediaModel = (config('lara-media.model'));
                $mediaModel::whereKey($this->fileAttr->id)->firstOrFail()->delete();
            } else {
                unlink($this->fileAttr->path . '/' . $fileName);
            }
        }

        $oldMedia = $this->model->media()->select('id');
        if (is_null($this->album)) {
            $oldMedia->whereNull('album');
        } else {
            $oldMedia->where('album', $this->album);
        }
        $oldMedia = $oldMedia->orderBy('created_at')->get();

        if ($oldMedia->count() > $this->limit) {
            Log::channel('lara-media')->debug('Limit reached for album ' . $this->album . ' ' . $oldMedia->count() .'/'. $this->limit);
            $oldMedia = $oldMedia->take($oldMedia->count() - $this->limit);
            \DB::transaction(function () use ($oldMedia) {
                foreach ($oldMedia as $o) {
                    $o->delete();
                }
            });
        }

        if (config('lara-media.photo.detect_main_color', false) === true &&
            $this->fileAttr->fileType === FileTypeEnum::Image) {
            $colorJob = new ColorDetectJob($media, $this);
        }

        if (!empty($this->responsive)) {
            if ($this->fileAttr->fileType === FileTypeEnum::Image) {
                $photoJob = new PhotoConvertJob($media, $this);
            } else if ($this->fileAttr->fileType === FileTypeEnum::Video) {
                $videoJob = new VideoConvertJob($media, $this);
            } else if ($this->fileAttr->fileType === FileTypeEnum::Audio) {
                $audioJob = new AudioConvertJob($media, $this);
            }
        }

        if (isset($colorJob) && isset($photoJob)) {
            $this->dispatch($photoJob->onQueue($this->queue)->onConnection($this->connection));
        } else if (isset($colorJob)) {
            $this->dispatch($colorJob->onQueue($this->queue)->onConnection($this->connection));
        } else if (isset($photoJob)) {
            $this->dispatch($photoJob->onQueue($this->queue)->onConnection($this->connection));
        } else if (isset($videoJob)) {
            $this->dispatch($videoJob->onQueue($this->queue)->onConnection($this->connection));
        } else if (isset($audioJob)) {
            $this->dispatch($audioJob->onQueue($this->queue)->onConnection($this->connection));
        }

        return $media;
    }
}
