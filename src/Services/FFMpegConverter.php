<?php

namespace Nhattuanbl\LaraMedia\Services;

use Exception;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Filters\Video\CustomFilter;
use FFMpeg\Format\Audio\Aac;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Flac;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Format\Audio\Wav;
use FFMpeg\Format\Video\DefaultVideo;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\WMV;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use Log;
use Nhattuanbl\LaraMedia\Contracts\FileAttr;
use Nhattuanbl\LaraMedia\Contracts\HLS;
use Nhattuanbl\LaraMedia\Enums\PositionEnum;
use Nhattuanbl\LaraMedia\Enums\ResolutionEnum;

class FFMpegConverter
{
    protected FFMpeg|FFProbe $ff;

    /** @var callable progress($percentage) */
    public $progress = null;
    /** @var callable progressThumb() */
    public $progressThumb = null;
    /** @var callable progressPoster() */
    public $progressPoster = null;

    protected ?int $quality = null;
    public string $format;
    protected string $input;
    protected string $output;
    protected string $name;
    protected string|array $encoding;
    protected DefaultAudio $audioCodec;
    protected DefaultVideo $videoCodec;
    protected bool $twoPass = false;
    protected int $thumbnailRow = 0;
    protected int $thumbnailCol = 0;
    protected int $poster = 0;
    protected ?int $posterWidth = null;
    protected ?string $watermarkPath = null;
    protected PositionEnum $watermarkPosition;
    protected float|int $watermarkOpacity;
    protected int $watermarkHeight;

    public float $conversionTook = 0;
    public float $posterTook = 0;
    public float $thumbnailTook = 0;
    public int $duration = 0;
    public array $posters = [];
    public ?string $thumbnail = null;

    public function __construct(public ?string $ffmpegPath = null, ?string $ffprobePath = null, int $threads = 8, int $timeout = 7200)
    {
        $options = [];
        if ($ffmpegPath) {
            $options['ffmpeg.binaries'] = $ffmpegPath;
        }
        if ($ffprobePath) {
            $options['ffprobe.binaries'] = $ffprobePath;
        }
        if ($threads > 1) {
            $options['ffmpeg.threads'] = $threads;
        }

        if ($timeout > 0) {
            $options['timeout'] = $timeout;
        }

        $this->ff = FFMpeg::create($options);
    }

    public function setQuality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function setFormat(string $format, string|array $encoder): self
    {
        match ($format) {
            'mp3'           => $this->audioCodec = new Mp3(),
            'aac', 'm4a'    => $this->audioCodec = new Aac(),
            'flac'          => $this->audioCodec = new Flac(),
            'wav'           => $this->audioCodec = new Wav(),

            '3gp', 'mkv', 'fli', 'mov', 'mp4', '3g2'    => $this->videoCodec = new X264(),
            'webm'                                      => $this->videoCodec = new WebM(),
            'ts'                                        => $this->videoCodec = new HLS(),
            'asf', 'wmv'                                => $this->videoCodec = new WMV(),

            default => throw new Exception('Invalid format ' . $format),
        };

        $this->encoding = $encoder;
        $this->format = $format;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setInput(string $input): self
    {
        if (!file_exists($input)) {
            throw new Exception('Input file not found');
        }

        $this->input = $input;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function setOutput(string $output): self
    {
        if (!is_writeable($output)) {
            throw new Exception('Output directory not writeable');
        }

        $this->output = $output;
        return $this;
    }

    public function setOutputName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setWatermark(string $path, PositionEnum $position, float|int $opacity, int $heightPercent): self
    {
        $this->watermarkPath = $path;
        $this->watermarkPosition = $position;
        $this->watermarkOpacity = $opacity;
        $this->watermarkHeight = $heightPercent;
        return $this;
    }

    public function setThumbnail(int $row, int $column): self
    {
        $this->thumbnailRow = $row;
        $this->thumbnailCol = $column;
        return $this;
    }

    public function setPoster(int $number, ?int $width): self
    {
        $this->poster = $number;
        $this->posterWidth = $width;
        return $this;
    }

    public function set2Pass(bool $twoPass = true): self
    {
        $this->twoPass = $twoPass;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function convertAudio(): string
    {
        $took = microtime(true);
        $audio = $this->ff->open($this->input);
        $audioInfo = $audio->getStreams()->audios()->first();
        $bitrate = $audioInfo->get('bit_rate');
        $channels = $audioInfo->get('channels');
        $this->duration = $audioInfo->get('duration');

        $this->audioCodec->setAudioCodec($this->encoding);
        $this->audioCodec->setAudioKiloBitrate($this->quality ?? $bitrate / 1000);
        $this->audioCodec->setAudioChannels($channels);

        $this->audioCodec->on('progress', function ($audio, $format, $percentage) {
            if (is_callable($this->progress)) {
                call_user_func($this->progress, $audio, $percentage, $this->output);
            }
        });

        $outputFile = $this->output . DIRECTORY_SEPARATOR . $this->name . '_tmp.' . $this->format;
        try {
            $audio->save($this->audioCodec, $outputFile);
            $this->conversionTook = (float) number_format(microtime(true) - $took, 2, '.', '');

            return $outputFile;
        } catch (Exception $e) {
            Log::channel('lara-media')->error($e->getPrevious() ?? $e->getMessage());
            throw new Exception($e->getPrevious() ?? $e);
        }
    }

    /**
     * @throws \Throwable
     */
    public function convertVideo(ResolutionEnum $resolution): string
    {
        $took = microtime(true);
        $video = $this->ff->open($this->input);
        $videoStream = $video->getStreams()->videos()->first();
        $audioStream = $video->getStreams()->audios()->first();

        $duration = $videoStream->get('duration');
        if (!$duration) {
            $ffprobe = FFProbe::create();
            $duration = $ffprobe->format($this->input)->get('duration');
        }
        $this->duration = $duration;
        $originalWidth = $videoStream->get('width');
        $originalHeight = $videoStream->get('height');
        $videoBitrate = $videoStream->get('bit_rate') ?? 1500 * 1024;
        $audioBitrate = $audioStream->get('bit_rate') ?? 128 * 1024;
        $sampleRate = $audioStream->get('sample_rate') ?? 44100;
        $channels = $audioStream->get('channels') ?? 2;
        $crf = max(0, min(51, round((100 - $this->quality) * 51 / 100)));

        $this->videoCodec->setAdditionalParameters(['-movflags', '+faststart']);
        $this->videoCodec->setVideoCodec($this->encoding[0]);
        $this->videoCodec->setAudioCodec($this->encoding[1]);
        $this->videoCodec->setAudioChannels($channels);
        $this->videoCodec->setAudioKiloBitrate((int) ($audioBitrate / 1024));
        $this->videoCodec->setAdditionalParameters(['-crf', $crf]);

        [$targetWidth, $targetHeight] = explode('x', $resolution->value);
        if ($originalWidth > $targetWidth) {
            [$targetWidth, $targetHeight] = $this->calcDimensions($originalWidth, $originalHeight, $targetWidth);
        } else {
            $targetWidth = $originalWidth;
            $targetHeight = $originalHeight;
        }

        $video->filters()->resize(new Dimension($targetWidth, $targetHeight));
        $scalingFactor = ($targetWidth * $targetHeight) / ($originalWidth * $originalHeight);
        $adjustedVideoBitrate = $scalingFactor * $videoBitrate;
        $this->videoCodec->setKiloBitrate((int) ($adjustedVideoBitrate / 1024));

        $outputFile = $this->output . DIRECTORY_SEPARATOR . $this->name . '_converted_' . $targetHeight . '.' . $this->format;
        $this->videoCodec->on('progress', function ($video, $format, $percentage) use ($resolution) {
            if (is_callable($this->progress)) {
                call_user_func($this->progress, $video, $percentage, $resolution->name);
            }
        });

        if ($this->twoPass) {
            $this->videoCodec->setPasses(1);
            try {
                $video->save($this->videoCodec, $outputFile);
            } catch (\Throwable $e) {
                Log::channel('lara-media')->error($e->getPrevious() ?? $e->getMessage());
                throw $e->getPrevious() ? new Exception($e->getPrevious()) : $e;
            }
            $this->videoCodec->setPasses(2);
        }

        if ($this->watermarkPath) {
            $watermarkHeight = ($this->watermarkHeight / 100) * $targetHeight;
            list($watermarkOriginalWidth, $watermarkOriginalHeight) = getimagesize($this->watermarkPath);
            $watermarkWidth = ($watermarkHeight / $watermarkOriginalHeight) * $watermarkOriginalWidth;
            $filter = new CustomFilter(
                'movie=' . escapeshellarg($this->watermarkPath) .
                ',scale=' . $watermarkWidth . ':' . $watermarkHeight .
                ',format=yuva420p,colorchannelmixer=aa=' . $this->watermarkOpacity .
                ' [watermark]; [in][watermark] overlay=' .
                $this->calcWatermarkX($targetWidth, $watermarkWidth) . ':' .
                $this->calcWatermarkY($targetHeight, $watermarkHeight) . ' [out]'
            );

            $video->addFilter($filter);
        }

        try {
            $video->save($this->videoCodec, $outputFile);
            $this->conversionTook = (float) number_format(microtime(true) - $took, 2, '.', '');
        } catch (\Throwable $e) {
            Log::channel('lara-media')->error($e->getPrevious() ?? $e->getMessage());
            throw $e->getPrevious() ? new Exception($e->getPrevious()) : $e;
        }

        if ($this->poster > 0) {
            $took = microtime(true);
            $this->posters = $this->genPosters($video);
            $this->posterTook = (float) number_format(microtime(true) - $took, 2, '.', '');
        }

        if ($this->thumbnailRow > 0) {
            $took = microtime(true);
            $this->thumbnail = $this->genThumbnail($video);
            $this->thumbnailTook = (float) number_format(microtime(true) - $took, 2, '.', '');
        }

        return $outputFile;
    }

    private function calcDimensions(int $originalWidth, int $originalHeight, int $targetWidth): array
    {
        $aspectRatio = $originalWidth / $originalHeight;
        $targetHeight = round($targetWidth / $aspectRatio);

        if ($targetHeight % 2 !== 0) {
            $targetHeight += 1;
        }

        if ($targetHeight < 2) {
            $targetHeight = 2;
        }

        $targetWidth = round($targetHeight * $aspectRatio);
        if ($targetWidth % 2 !== 0) {
            $targetWidth += 1;
        }

        if ($targetWidth < 2) {
            $targetWidth = 2;
        }

        return [$targetWidth, $targetHeight];
    }

    private function calcWatermarkX(int $videoWidth, int $watermarkWidth): string
    {
        return match ($this->watermarkPosition) {
            PositionEnum::TopLeft, PositionEnum::BottomLeft => '10',
            PositionEnum::TopRight, PositionEnum::BottomRight => "main_w-overlay_w-10",
            default => "(main_w-overlay_w)/2",
        };
    }

    private function calcWatermarkY(int $videoHeight, int $watermarkHeight): string
    {
        return match ($this->watermarkPosition) {
            PositionEnum::TopLeft, PositionEnum::TopRight => '10',
            PositionEnum::BottomLeft, PositionEnum::BottomRight => "main_h-overlay_h-10",
            default => "(main_h-overlay_h)/2",
        };
    }

    /**
     * @throws ImagickException
     */
    private function genPosters(Video $video): array
    {
        $timestamps = [5];
        if ($this->duration > 10 && $this->poster > 1) {
            for ($i = 1; $i <= ($this->poster - 1); $i++) {
                $timestamps[] = round($i * ($this->duration / $this->poster));
            }
        }

        $thumbnailPaths = [];
        $i = 0;
        foreach ($timestamps as $x) {
            $i++;
            $thumbnailPath = $this->output . DIRECTORY_SEPARATOR . $this->name . '_converted_' . $x . '.jpg';
            $video->frame(TimeCode::fromSeconds($x))->save($thumbnailPath);

            if ($this->posterWidth) {
                $imagick = new Imagick($thumbnailPath);
                $imagick->resizeImage($this->posterWidth, 0, Imagick::FILTER_LANCZOS, 1);
                $imagick->writeImage($thumbnailPath);
                $imagick->clear();
                $imagick->destroy();
            }

            if (is_callable($this->progressPoster)) {
                call_user_func($this->progressPoster, $video, $i, count($timestamps), $x);
            }

            $thumbnailPaths[$x] = $thumbnailPath;
        }

        return $thumbnailPaths;
    }

    /**
     * @throws ImagickDrawException
     * @throws ImagickException
     * @throws Exception
     */
    private function genThumbnail(Video $video): string
    {
        $totalChild = $this->thumbnailRow * $this->thumbnailCol;
        $timestamps = [];
        for ($i = 1; $i <= $totalChild; $i++) {
            $timestamps[] = round($i * ($this->duration / ($totalChild + 1)));
        }

        $thumbnailPaths = [];
        $i = 0;
        foreach ($timestamps as $x) {
            $i++;
            $thumbnailPath = $this->output . DIRECTORY_SEPARATOR . $this->name . '_tmp_' . $x . '.jpg';
            $video->frame(TimeCode::fromSeconds($x))->save($thumbnailPath);
            if (is_callable($this->progressThumb)) {
                call_user_func($this->progressThumb, $video, $i, count($timestamps), $x);
            }
            $thumbnailPaths[] = $thumbnailPath;
        }

        $images = [];
        foreach ($thumbnailPaths as $path) {
            $img = new Imagick($path);
            $images[] = $img;
        }

        $imgWidth = $images[0]->getImageWidth();
        $imgHeight = $images[0]->getImageHeight();
        $padding = 10;
        $totalWidth = $imgWidth * $this->thumbnailCol + $padding * ($this->thumbnailCol + 1);
        $totalHeight = $imgHeight * $this->thumbnailRow + $padding * ($this->thumbnailRow + 1);

        $composite = new Imagick();
        try {
            $composite->newImage($totalWidth, $totalHeight + 70, new ImagickPixel('white'));
        } catch (Exception $e) {
            Log::channel('lara-media')->error('Unable to create image ' . $e->getMessage(), [
                'width' => $totalWidth,
                'height' => $totalHeight,
            ]);
            throw new Exception($e);
        }


        $draw = new ImagickDraw();
        $draw->setFontSize(14);
        $draw->setFillColor(new ImagickPixel('black'));
        $draw->setTextAlignment(\Imagick::ALIGN_LEFT);

        $videoStream = $video->getStreams()->videos()->first();
        $videoWidth = $videoStream->get('width');
        $videoHeight = $videoStream->get('height');
        $videoBytes = filesize($video->getPathfile());
        $composite->annotateImage($draw, 25, 25, 0,
            "File size: " . FileAttr::byte2Readable($videoBytes) . " (".number_format($videoBytes)." bytes)\n" .
            "Resolution: {$videoWidth}x{$videoHeight}\n" .
            "Duration: " . gmdate("H:i:s", $videoStream->get('duration'))
        );

        $x = $padding;
        $y = 70;
        foreach ($images as $index => $img) {
            $composite->compositeImage($img, Imagick::COMPOSITE_DEFAULT, $x, $y);
            $x += $imgWidth + $padding;
            if (($index + 1) % $this->thumbnailCol == 0) {
                $x = $padding;
                $y += $imgHeight + $padding;
            }
        }

        $output = $this->output . DIRECTORY_SEPARATOR . $this->name . '_thumbs.jpg';;
        $composite->setImageFormat('jpg');
        $composite->writeImage($output);

        foreach ($images as $img) {
            $img->clear();
            $img->destroy();
        }
        foreach ($thumbnailPaths as $path) {
            unlink($path);
        }
        $composite->clear();
        $composite->destroy();

        return $output;
    }
}
