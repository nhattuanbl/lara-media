<?php

namespace Nhattuanbl\LaraMedia\Jobs;

use FFMpeg\Media\Video;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Log;
use Nhattuanbl\LaraMedia\Contracts\FileAttr;
use Nhattuanbl\LaraMedia\Contracts\VideoRegister;
use Nhattuanbl\LaraMedia\Exceptions\DownloadFailedException;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Imtigger\LaravelJobStatus\Trackable;
use Nhattuanbl\LaraMedia\Services\FFMpegConverter;

class VideoConvertJob implements ShouldQueue
{
    use Queueable, Trackable;

    public $timeout = 7200;
    public $failOnTimeout = true;

    private int $totalState = 0;
    private int $currentState = 0;

    public function __construct(#[WithoutRelations] public LaraMedia $media, public VideoRegister $config)
    {
        $this->afterCommit();
        $this->prepareStatus(['model_type' => get_class($media), 'model_id' => $media->id]);
        $this->timeout = config('lara-media.video.timeout', 7200);
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
            RequestOptions::PROGRESS        => function($downloadTotal, $downloadedBytes) {
                $percent = $downloadedBytes / $this->media->size * 100;
                $this->setProgressNow((int) $percent, 2);
            },
        ])->get($url);

        if (!$response->successful()) {
            throw new DownloadFailedException($url, $response->status());
        }
    }

    private function setState(string $msg): void
    {
        $this->setOutput(['message' => $this->currentState . '/' . $this->totalState . ' ' . $msg . '....']);
    }

    private function moveTempFile(string $localPath, string $remotePath): void
    {
        if (FileAttr::isLocalDisk($this->config->conversionDisk)) {
            rename($localPath, Storage::disk($this->config->conversionDisk)->path($remotePath));
        } else {
            $fp = fopen($localPath, 'r');
            Storage::disk($this->config->conversionDisk)->writeStream($remotePath, $fp);
            fclose($fp);
            unlink($localPath);
        }
    }

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        if ($this->media->is_removed) {
            throw new \Exception('Media '.$this->media->id.' origin is removed');
        }

        $tempDir = config('lara-media.conversion.temp');
        $this->setProgressMax(100);
        $this->totalState = count($this->config->responsive);
        $this->currentState = 0;

        if (FileAttr::isLocalDisk($this->media->disk)) {
            $filePath = Storage::disk($this->media->disk)->path($this->media->path . '/' . $this->media->name . '.' . $this->media->ext);
        } else {
            $this->totalState++;
            $this->currentState++;
            $this->setState('downloading');

            $filePath = $tempDir . DIRECTORY_SEPARATOR . $this->media->id . '.' . $this->media->ext;
            $url = Storage::disk($this->media->disk)->url($this->media->path . '/' . $this->media->name . '.' . $this->media->ext);
            $this->download($url, $filePath);
        }

        usort($this->config->responsive, fn($a, $b) => $a->name <=> $b->name );
        $encoding = config('lara-media.video.encoding');
        $total_took = 0;
        $isFirstConversion = 0;
        $result = new Collection;

        foreach ($this->config->responsive as $resolution) {
            $isFirstConversion++;
            $this->currentState++;

            $convert = new FFMpegConverter(
                config('lara-media.conversion.ffmpeg_path'),
                config('lara-media.conversion.ffprobe_path'),
                config('lara-media.conversion.threads', 16),
                $this->timeout,
            );

            $convert->setInput($filePath)
                ->setOutput($tempDir)
                ->setOutputName($this->media->id)
                ->setQuality($this->config->quality)
                ->setFormat($this->config->format, $encoding[$this->config->format]);

            $convert->progress = function(Video $video, int|float $percent, $res) {
                $this->setProgressNow((int) $percent, 2);
                $this->setState('conversion ' . str_replace('H', '', $res) . 'p');
            };

            $convert->progressPoster = function(Video $video, int $current, int $total, int $ts) {
                $this->currentState++;
                $this->setState('generating poster timestamp ' . $ts);
            };

            $convert->progressThumb = function(Video $video, int $current, int $total, int $ts) {
                $this->currentState++;
                $this->setState('generating thumbnail timestamp ' . $ts);
            };

            if ($this->config->watermarkPath) {
                $convert->setWatermark($this->config->watermarkPath, $this->config->watermarkPosition, $this->config->watermarkOpacity, $this->config->watermarkHeight);
            }

            if ($this->config->twoPass === true) {
                $convert->set2Pass();
            }

            if ($isFirstConversion === 1) {
                if ($this->config->thumbnailCol > 0 && $this->config->thumbnailRow > 0) {
                    $this->totalState += $this->config->thumbnailCol * $this->config->thumbnailRow;
                    $convert->setThumbnail($this->config->thumbnailRow, $this->config->thumbnailCol);
                }

                if ($this->config->posters > 0) {
                    $this->totalState += $this->config->posters;
                    $convert->setPoster($this->config->posters, $this->config->posterWidth);
                }
            }

            $tempConverted = $convert->convertVideo($resolution);
            $total_took += $convert->conversionTook;
            $total_took += $convert->posterTook;
            $total_took += $convert->thumbnailTook;
            $duration = $convert->duration;

            $result->push((object) [
                'path' => $tempConverted,
                'took' => $convert->conversionTook,
                'size' => filesize($tempConverted),
                'duration' => $duration,
                'format' => $convert->format,
                'posterTook' => $convert->posterTook,
                'thumbnailTook' => $convert->thumbnailTook,
                'resolution' => str_replace('H', '', $resolution->name),
                'thumbnail' => $convert->thumbnail,
                'posters' => $convert->posters,
            ]);

            unset($convert);
        }

        $properties = $this->media->properties;
        $properties['conversion_disk'] = $this->config->conversionDisk;
        $properties['video_duration'] = $result->first()->duration;
        $properties['took'] = (float) number_format($total_took, 2);
        $properties['ext'] = $result->first()->format;
        $this->media->properties = $properties;

        $conversions = $this->media->conversions;
        $total_files = 0;
        $total_size = 0;
        foreach ($result as $r) {
            $total_files++;
            $total_size += $r->size;
            $filename = $this->media->name . '_converted_' . $r->resolution . '.' . $r->format;
            $this->moveTempFile($r->path, $this->media->path . DIRECTORY_SEPARATOR . $filename);
            $conversions[$r->resolution] = [
                'size' => $r->size,
                'took' => $r->took,
            ];
        }
        $this->media->conversions = $conversions;

        $responsive = $this->media->responsive;
        if (!empty($result->first()->posters)) {
            foreach ($result->first()->posters as $ts => $poster) {
                $total_files++;
                $total_size += filesize($poster);
                $responsive[$ts] = [
                    'size' => filesize($poster),
                    'took' => $result->first()->posterTook,
                ];
                $filename = $this->media->name . '_' . $ts . '.jpg';
                $this->moveTempFile($poster, $this->media->path . DIRECTORY_SEPARATOR . $filename);
            }
        }

        if (isset($result->first()->thumbnail)) {
            $total_files++;
            $total_size += filesize($result->first()->thumbnail);
            $responsive['thumb'] = [
                'size' => filesize($result->first()->thumbnail),
                'took' => $result->first()->thumbnailTook,
            ];
            $filename = $this->media->name . '_thumb.jpg';
            $this->moveTempFile($result->first()->thumbnail, $this->media->path . DIRECTORY_SEPARATOR . $filename);
        }

        $this->media->responsive = $responsive;
        $this->media->total_files += $total_files;
        $this->media->total_size += $total_size;

        if ($this->config->keepOrigin === false) {
            Storage::disk($this->media->disk)->delete($this->media->path . '/' . $this->media->name . '.' . $this->media->ext);
            $this->media->is_removed = true;
            $this->media->total_files--;
        }

        $this->media->save();
        $this->setOutput([]);
    }
}
