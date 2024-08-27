<?php

namespace Nhattuanbl\LaraMedia\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Nhattuanbl\LaraHelper\Helpers\FileHelper;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Nhattuanbl\LaraMedia\Services\Convert;
use Nhattuanbl\LaraMedia\Services\LaraMediaService;
use Imtigger\LaravelJobStatus\Trackable;

class LaraMediaConversionJob implements ShouldQueue
{
    use Queueable, Trackable;

    public $timeout = 7200;
    public $failOnTimeout = true;

    public function __construct(public LaraMedia $media, public LaraMediaService $videoService)
    {
        $this->timeout = config('lara-media.video.timeout', 7200);
        $this->afterCommit();
        $this->prepareStatus(['model_type' => get_class($this->media), 'model_id' => $this->media->id]);
    }

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        if ($this->media->is_removed) {
            throw new \Exception('Media '.$this->media->id.' origin is removed');
        }

        $links = [];
        $temporary = storage_path(config('lara-media.temp', 'temp'));
        $thread = config('lara-media.conversion.threads', 16);

        if (!file_exists($temporary)) {
            mkdir($temporary);
            file_put_contents($temporary . '/.gitignore', "*\n!.gitignore\n");
        }

        $ext = 'mp4';
        usort($this->videoService->resolution, fn($a, $b) => $b->value <=> $a->value );
        $this->setProgressMax(count($this->videoService->resolution));

        for($i = 0; $i < count($this->videoService->resolution); $i++) {
            $this->incrementProgress();
            $resolution = $this->videoService->resolution[$i];
            $this->setOutput(['msg' => 'Conversion resolution '.$resolution->value]);

            $convert = (new Convert(threads: $thread))
                ->setName($this->media->id)
                ->setInput(FileHelper::isLocalDisk($this->media->disk)
                    ? Storage::disk($this->media->disk)->path($this->media->path . '/' . $this->media->name . '.' . $this->media->ext)
                    : $this->media->url)
                ->setOutput($temporary, true)
//                ->setFormat('mp4')
//                ->setEncoding()
                ->setResolution($resolution);

            if ($i == 0 && $this->videoService->thumbnail > 0) {
                $convert->setThumbnail($this->videoService->thumbnail, $this->videoService->thumbnailWidth);
            }

            if ($i == 0 && !empty($this->videoService->compositeThumbnail)) {
                $convert->setCompositeThumbnail($this->videoService->compositeThumbnail[0], $this->videoService->compositeThumbnail[1]);
            }

            if ($this->videoService->watermark_path) {
                $convert->setWatermark($this->videoService->watermark_path, $this->videoService->watermark_size, $this->videoService->watermark_position, $this->videoService->watermark_opacity);
            }

            $convert->progress = function (int $progress) use ($resolution) {
                \Cache::put('lara-media-process:' . $this->media->id . ':' . $resolution->name, $progress, now()->addMinutes(5));
            };

            $links[] = $convert->encode();
            $ext = $convert->getFormat();
        }

        $result = [];
        foreach ($links as $l) {
            $key = array_key_first($l['video']);
            $result['video'][$key] = $l['video'][$key];
            (isset($l['images']) ? $result['images'] = $l['images'] : null);
            (isset($l['thumbs']) ? $result['thumbs'] = $l['thumbs'] : null);
            $result['took'] = ($result['took'] ?? 0) + $l['took'];
        }

        $responsive = $this->media->responsive;
        foreach ($result['images'] as $time => $path) {
            $this->setOutput(['msg' => 'Moving responsive image '.$time]);

            $filesize = filesize($path);
            $responsive[$time] = ['size' => $filesize];
            $this->media->total_files++;
            $this->media->total_size += $filesize;

            $name = '/' . $this->media->name . '_' . $time . '.jpg';
            if (FileHelper::isLocalDisk($this->videoService->disk)) {
                rename($path, Storage::disk($this->videoService->disk)->path($this->media->path . $name));
            } else {
                Storage::disk($this->videoService->disk)->put($this->media->path . $name, file_get_contents($path));
                unlink($path);
            }
        }

        $responsive['thumbs'] = null;
        if (isset($result['thumbs'])) {
            $this->setOutput(['msg' => 'Moving responsive thumbnail']);

            $filesize = filesize($result['thumbs']);
            $responsive['thumbs'] = ['size' => $filesize];
            $this->media->total_files++;
            $this->media->total_size += $filesize;

            $name = '/' . $this->media->name . '_thumb.jpg';
            $path = $result['thumbs'];
            if (FileHelper::isLocalDisk($this->videoService->disk)) {
                rename($path, Storage::disk($this->videoService->disk)->path($this->media->path . $name));
            } else {
                Storage::disk($this->videoService->disk)->put($this->media->path . $name, file_get_contents($path));
                unlink($path);
            }
        }
        $this->media->responsive = $responsive;

        $properties = $this->media->properties;
        $properties['conversions_disk'] = $this->videoService->disk;
        $properties['conversions_took'] = $result['took'];
        $properties['conversions_ext'] = $ext;
        $this->media->properties = $properties;

        $conversions = $this->media->conversions;
        foreach ($result['video'] as $resolution => $mp4) {
            $this->setOutput(['msg' => 'Moving conversion resolution '.$resolution]);

            $filesize = filesize($mp4);
            $conversions[$resolution] = ['size' => $filesize];
            $this->media->total_files++;
            $this->media->total_size += $filesize;

            $name = '/' . $this->media->name . '_' . $resolution . '.' . $ext;
            if (FileHelper::isLocalDisk($this->videoService->disk)) {
                rename($mp4, Storage::disk($this->videoService->disk)->path($this->media->path . $name));
            } else {
                $fp = fopen($mp4, 'r');
                Storage::disk($this->videoService->disk)->put($this->media->path . $name, $fp);
                fclose($fp);
                unlink($mp4);
            }
        }
        $this->media->conversions = $conversions;

        if ($this->videoService->deleteOriginal) {
            Storage::disk($this->media->disk)->delete($this->media->path . '/' . $this->media->name . '.' . $this->media->ext);
            $this->media->total_files--;
            $this->media->is_removed = true;
        }

        $this->media->save();
    }
}
