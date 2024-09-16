<?php

namespace Nhattuanbl\LaraMedia\Jobs;

use FFMpeg\Media\Audio;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Imtigger\LaravelJobStatus\Trackable;
use Nhattuanbl\LaraMedia\Contracts\AudioRegister;
use Nhattuanbl\LaraMedia\Contracts\FileAttr;
use Nhattuanbl\LaraMedia\Exceptions\DownloadFailedException;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Nhattuanbl\LaraMedia\Services\FFMpegConverter;

class AudioConvertJob implements ShouldQueue
{
    use Queueable, Trackable;

    public $timeout = 7200;
    public $failOnTimeout = true;

    public function __construct(#[WithoutRelations] public LaraMedia $media, public AudioRegister $config)
    {
        $this->afterCommit();
        $this->prepareStatus(['model_type' => get_class($media), 'model_id' => $media->id]);
        $this->timeout = config('lara-media.audio.timeout', 7200);
    }

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        if ($this->media->is_removed) {
            throw new \Exception('Media '.$this->media->id.' is removed');
        }

        $tempDir = config('lara-media.conversion.temp');
        $this->setProgressMax(100);
        $totalState = count($this->config->responsive);
        $currentState = 0;

        if (FileAttr::isLocalDisk($this->media->disk)) {
            $filePath = Storage::disk($this->media->disk)->path($this->media->path . '/' . $this->media->name . '.' . $this->media->ext);
        } else {
            $totalState++;
            $currentState++;
            $this->setStatus($currentState, $totalState, 'downloading');

            $filePath = $tempDir . DIRECTORY_SEPARATOR . $this->media->id . '.' . $this->media->ext;
            $url = Storage::disk($this->media->disk)->url($this->media->path . '/' . $this->media->name . '.' . $this->media->ext);
            $this->download($url, $filePath);
        }

        $convert = new FFMpegConverter(
            config('lara-media.conversion.ffmpeg_path'),
            config('lara-media.conversion.ffprobe_path'),
            config('lara-media.conversion.threads', 16),
            $this->timeout,
        );

        $convert->setInput($filePath)
            ->setOutput($tempDir)
            ->setOutputName($this->media->id);

        if ($this->config->quality) {
            $convert->setQuality($this->config->quality);
        }

        $convert->progress = function(Audio $audio, int|float $percent) {
            $this->setProgressNow((int) $percent, 2);
        };

        $encoding = config('lara-media.audio.encoding');
        $result = new Collection;
        foreach ($this->config->responsive as $x) {
            $currentState++;
            $this->setStatus($currentState, $totalState, 'converting');
            $outputFile = $convert->setFormat($x, $encoding[$x])->convertAudio();
            $result->push((object) [
                'path' => $outputFile,
                'duration' => $convert->duration,
                'took' => $convert->conversionTook,
                'size' => filesize($outputFile),
                'format' => $convert->format
            ]);
        }

        $properties = $this->media->properties;
        $properties['conversion_disk'] = $this->config->conversionDisk;
        $properties['audio_duration'] = $result->first()->duration;
        $properties['took'] = $result->sum('took');
        $this->media->properties = $properties;

        $conversions = $this->media->conversions;
        foreach ($result as $x) {
            $filename = $this->media->name . '_converted.' . $x->format;
            if (FileAttr::isLocalDisk($this->config->conversionDisk)) {
                rename($x->path, Storage::disk($this->config->conversionDisk)->path($this->media->path . DIRECTORY_SEPARATOR . $filename));
            } else {
                $fp = fopen($x->path, 'r');
                Storage::disk($this->config->conversionDisk)->writeStream($this->media->path . DIRECTORY_SEPARATOR . $filename, $fp);
                fclose($fp);
                unlink($x->path);
            }

            $conversions[$x->format] = [
                'size' => $x->size,
                'took' => $x->took,
            ];
            $this->media->total_files++;
            $this->media->total_size += $x->size;
        }
        $this->media->conversions = $conversions;

        if ($this->config->keepOrigin === false) {
            Storage::disk($this->media->disk)->delete($this->media->path . '/' . $this->media->name . '.' . $this->media->ext);
            $this->media->is_removed = true;
            $this->media->total_files--;
        }

        $this->media->save();
        $this->setOutput([]);
    }

    /**
     * @throws DownloadFailedException
     * @throws ConnectionException
     */
    protected function download(string $url, string $output): void
    {
        $response = Http::withOptions([
            RequestOptions::HTTP_ERRORS     => false,
            RequestOptions::VERIFY          => false,
            RequestOptions::SINK            => $output,
            RequestOptions::ALLOW_REDIRECTS => true,
            RequestOptions::PROGRESS        => function($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) {
                $percent = $downloadedBytes / $this->media->size * 100;
                $this->setProgressNow((int) $percent, 2);
            },
        ])->get($url);

        if (!$response->successful()) {
            throw new DownloadFailedException($url, $response->status());
        }
    }

    protected function setStatus(int $current, int $total, string $msg): void
    {
        $this->setOutput(['message' => $current . '/' . $total . ' ' . $msg . '....']);
    }
}
