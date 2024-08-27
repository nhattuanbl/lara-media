<?php

namespace Nhattuanbl\LaraMedia\Services;

use Exception;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Filters\Video\CustomFilter;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Audio;
use FFMpeg\Media\Video;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use Nhattuanbl\LaraHelper\Helpers\FileHelper;
use Nhattuanbl\LaraMedia\Contracts\HLS;
use Nhattuanbl\LaraMedia\Enums\PositionEnum;
use Nhattuanbl\LaraMedia\Enums\ResolutionEnum;

class Convert
{
    protected FFMpeg|FFProbe $ff;
    protected string $inputPath;
    protected string $outputPath;
    protected array $encoding = [
        'video' => 'libx264',
        'audio' => 'aac',
        'format' => 'mp4',
    ];
    protected bool $twoPass = false;
    protected bool $removeExist = false;
    protected int $thumbnails = 0;
    protected ?int $thumbnailWidth = null;
    protected array $compositeThumbnail = [];
    protected int $quality = 55;
    protected ?string $watermarkPath = null;
    protected int|float $watermarkOpacity = 0.2;
    protected int|float $watermarkSizePercent = 10;
    protected PositionEnum $watermarkPosition = PositionEnum::Center;
    protected ResolutionEnum $resolution;
    protected ?string $name;
    public $progress;

    public function __construct(public ?string $ffmpegPath = null, ?string $ffprobePath = null, int $threads = 8)
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

        $this->ff = FFMpeg::create($options);
    }

    /**
     * @throws Exception
     */
    public function setInput(string $path): self
    {
        if (!file_exists($path)) {
            throw new Exception($path . ' not found');
        }
        $this->inputPath = $path;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function setOutput(string $dir, bool $removeExist = false): self
    {
        if (!is_writeable($dir)) {
            throw new Exception($dir . ' is not writeable');
        }

        $this->outputPath = rtrim($dir, '/');
        $this->removeExist = $removeExist;
        return $this;
    }

    public function setEncoding(string $videoCodec, string $audioCodec): self
    {
        $this->encoding['video'] = $videoCodec;
        $this->encoding['audio'] = $audioCodec;
        return $this;
    }

    public function setWatermark(string $path, int|float $sizePercent = 40, PositionEnum $position = PositionEnum::Center, float|int $opacity = 0.2): self
    {
        if (!file_exists($path)) {
            throw new Exception($path . ' not found');
        }

        $this->watermarkPath = $path;
        $this->watermarkOpacity = $opacity;
        $this->watermarkPosition = $position;
        $this->watermarkSizePercent = $sizePercent;
        return $this;
    }

    public function setResolution(ResolutionEnum $resolution): self
    {
        $this->resolution = $resolution;
        return $this;
    }

    public function setThumbnail(int $thumbnails = 1, ?int $width = null): self
    {
        $this->thumbnails = $thumbnails;
        $this->thumbnailWidth = $width;
        return $this;
    }

    public function setCompositeThumbnail(int $row = 4, int $column = 4): self
    {
        $this->compositeThumbnail = [$row, $column];
        return $this;
    }

    public function setFormat(string $format): self
    {
        match ($format) {
            'mp4', 'webm', 'hls' => $this->encoding['format'] = $format,
            default => throw new Exception("Unsupported video format: " . $this->encoding['format']),
        };

        return $this;
    }

    public function getFormat(): string
    {
        return $this->encoding['format'];
    }

    public function setTwoPass(bool $twoPass = true): self
    {
        $this->twoPass = $twoPass;
        return $this;
    }

    public function setQuality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? pathinfo($this->inputPath, PATHINFO_FILENAME);
    }

    /**
     * @throws Exception
     */
    public function encode(): array
    {
        $took = time();
        $video = $this->ff->open($this->inputPath);

        $videoStream = $video->getStreams()->videos()->first();
        $audioStream = $video->getStreams()->audios()->first();

        $originalWidth = $videoStream->get('width');
        $originalHeight = $videoStream->get('height');
        $aspectRatio = $originalWidth / $originalHeight;
        $videoBitrate = $videoStream->get('bit_rate') ?? 1500 * 1024;
        $audioBitrate = $audioStream->get('bit_rate') ?? 128 * 1024;
        $sampleRate = $audioStream->get('sample_rate') ?? 44100;
        $channels = $audioStream->get('channels') ?? 2;
        $duration = $videoStream->get('duration');
        $crf = max(0, min(51, round((100 - $this->quality) * 51 / 100)));
        [$targetWidth, $targetHeight] = explode('x', $this->resolution->value);

        if ($originalWidth > $targetWidth) {
            [$targetWidth, $targetHeight] = $this->adjustDimensionsToMaintainAspectRatio($originalWidth, $originalHeight, $targetWidth);
        } else {
            $targetWidth = $originalWidth;
            $targetHeight = $originalHeight;
        }

        $videoFormat = match ($this->encoding['format']) {
            'mp4' => new X264(),
            'webm' => new WebM(),
            'hls' => new HLS(),
            default => throw new Exception("Unsupported video format: " . $this->encoding['format']),
        };

        $output = $this->outputPath . '/' . $this->getName() . '_' . $targetHeight . '.' . $this->encoding['format'];
        if ($this->removeExist && file_exists($output)) {
            \Log::channel('lara-media')->debug($output . ' already exists. Removing...');
            unlink($output);
        } else if ($this->removeExist === false && file_exists($output)) {
            throw new Exception($output . ' already exists');
        }

        $videoFormat->setAudioCodec($this->encoding['audio']);
        $videoFormat->setVideoCodec($this->encoding['video']);
        $videoFormat->setKiloBitrate((int) ($videoBitrate / 1024));
        $videoFormat->setAudioChannels($channels);
        $videoFormat->setAudioKiloBitrate((int) ($audioBitrate / 1024));
        $videoFormat->setAdditionalParameters(['-crf', $crf]);

        $videoFormat->on('progress', function ($video, $format, $percentage) {
            if (is_callable($this->progress)) {
                ($this->progress)($percentage);
            }
        });
        $video->filters()->resize(new Dimension($targetWidth, $targetHeight));

        if ($this->twoPass) {
            $videoFormat->setPasses(1);
            try {
                $video->save($videoFormat, $output);
            } catch (\Throwable $e) {
                throw new Exception($e->getPrevious());
            }
            $videoFormat->setPasses(2);
        }

        if ($this->watermarkPath) {
            $watermarkHeight = ($this->watermarkSizePercent / 100) * $targetHeight;
            list($watermarkOriginalWidth, $watermarkOriginalHeight) = getimagesize($this->watermarkPath);
            $watermarkWidth = ($watermarkHeight / $watermarkOriginalHeight) * $watermarkOriginalWidth;

            $filter = new CustomFilter(
                'movie=' . escapeshellarg($this->watermarkPath) .
                ',scale=' . $watermarkWidth . ':' . $watermarkHeight .
                ',format=yuva420p,colorchannelmixer=aa=' . $this->watermarkOpacity .
                ' [watermark]; [in][watermark] overlay=' .
                $this->getOverlayPositionX($targetWidth, $watermarkWidth) . ':' .
                $this->getOverlayPositionY($targetHeight, $watermarkHeight) . ' [out]'
            );

            $video->addFilter($filter);
        }

        try {
            $video->save($videoFormat, $output);
            $result['video'] = [$targetHeight => $output];
            $result['took'] = time() - $took;
        } catch (\Throwable $e) {
            \Log::channel('lara-media')->error($this->getName() . ' Error encoding', ['error' => $e->getPrevious()]);
            throw new Exception($e->getPrevious());
        }

        if ($duration < 5) {
            return $result;
        }

        if ($this->thumbnails > 0) {
            $videoOutput = $this->ff->open($output);
            $thumbnailTimestamps = [5];
            if ($duration > 10 && $this->thumbnails > 1) {
                $thumbnailTimestamps = array_merge($thumbnailTimestamps, $this->calculateThumbnailTimestamps($duration, $this->thumbnails - 1));
            }
            $result['images'] = $this->generateThumbnails($videoOutput, $thumbnailTimestamps);
        }

        if (!empty($this->compositeThumbnail) && count($this->compositeThumbnail) == 2) {
            $videoOutput = $videoOutput ?? $this->ff->open($output);
            $thumbnailTimestamps = $this->calculateThumbnailTimestamps($duration, $this->compositeThumbnail[0] * $this->compositeThumbnail[1]);
            $result['thumbs'] = $this->generateThumbnails($videoOutput, $thumbnailTimestamps, true);
        }

        $result['took'] = time() - $took;
        return $result;
    }

    protected function getOverlayPositionX(int $targetWidth, int $watermarkWidth): string
    {
        return match ($this->watermarkPosition) {
            PositionEnum::TopLeft, PositionEnum::BottomLeft => '10',
            PositionEnum::TopRight, PositionEnum::BottomRight => "main_w-overlay_w-10",
            default => "(main_w-overlay_w)/2",
        };
    }

    protected function getOverlayPositionY(int $targetHeight, int $watermarkHeight): string
    {
        return match ($this->watermarkPosition) {
            PositionEnum::TopLeft, PositionEnum::TopRight => '10',
            PositionEnum::BottomLeft, PositionEnum::BottomRight => "main_h-overlay_h-10",
            default => "(main_h-overlay_h)/2",
        };
    }

    protected function adjustDimensionsToMaintainAspectRatio(int $originalWidth, int $originalHeight, int $targetWidth): array
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

    protected function calculateThumbnailTimestamps(float $duration, int $total): array
    {
        $result = [];
        $interval = $duration / ($total + 1);
        for ($i = 1; $i <= $total; $i++) {
            $result[] = round($i * $interval);
        }

        return $result;
    }

    /**
     * @throws \ImagickException
     * @throws Exception
     */
    protected function generateThumbnails(Audio|Video $video, array $thumbnailTimestamps, bool $composite = false): array|string
    {
//        $thumbnailTimestamps = array_unique($thumbnailTimestamps);
        $ext = $composite ? '.thumb' : '.jpg';
        $generated = [];
        foreach ($thumbnailTimestamps as $x) {
            $thumbnailPath = $this->outputPath . '/' . $this->getName() . '_' . $x . $ext;
            try {
                $video->frame(TimeCode::fromSeconds($x))->save($thumbnailPath);
            } catch (\Throwable $e) {
                \Log::channel('lara-media')->error($this->getName() . ' Error generating thumbnail', ['error' => $e->getPrevious()]);
                throw new Exception($e->getPrevious());
            }

            if ($this->thumbnailWidth) {
                $imagick = new Imagick($thumbnailPath);
                $imagick->resizeImage($this->thumbnailWidth, 0, Imagick::FILTER_LANCZOS, 1);
                $imagick->writeImage($thumbnailPath);
                $imagick->clear();
                $imagick->destroy();
            }

            $generated[$x] = $thumbnailPath;
        }

        if ($composite) {
            return $this->generateCompositeThumb($video, $generated);
        }

        return $generated;
    }

    /**
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    protected function generateCompositeThumb(Audio|Video $video, array $imagePaths): string
    {
        $imagePaths = array_values($imagePaths);
        $rows = $this->compositeThumbnail[0];
        $cols = $this->compositeThumbnail[1];
        $images = [];
        foreach ($imagePaths as $path) {
            $img = new Imagick($path);
            $images[] = $img;
        }

        $imgWidth = $images[0]->getImageWidth();
        $imgHeight = $images[0]->getImageHeight();
        $padding = 10;
        $totalWidth = $imgWidth * $cols + $padding * ($cols + 1);
        $totalHeight = $imgHeight * $rows + $padding * ($rows + 1);

        $composite = new Imagick();
        $composite->newImage($totalWidth, $totalHeight + 70, new ImagickPixel('white'));

        $draw = new ImagickDraw();
        $draw->setFontSize(14);
        $draw->setFillColor(new ImagickPixel('black'));
        $draw->setTextAlignment(\Imagick::ALIGN_LEFT);

        $videoStream = $video->getStreams()->videos()->first();
        $videoWidth = $videoStream->get('width');
        $videoHeight = $videoStream->get('height');
        $videoBytes = filesize($video->getPathfile());
        $composite->annotateImage($draw, 25, 25, 0,
            "File size: " . FileHelper::byte2Readable($videoBytes) . " (".number_format($videoBytes)." bytes)\n" .
            "Resolution: {$videoWidth}x{$videoHeight}\n" .
            "Duration: " . gmdate("H:i:s", $videoStream->get('duration'))
        );

        $x = $padding;
        $y = 70;
        foreach ($images as $index => $img) {
            $composite->compositeImage($img, Imagick::COMPOSITE_DEFAULT, $x, $y);
            $x += $imgWidth + $padding;
            if (($index + 1) % $cols == 0) {
                $x = $padding;
                $y += $imgHeight + $padding;
            }
        }

        $output = $this->outputPath . '/' . $this->getName() . '_thumbs.jpg';
        $composite->setImageFormat('jpg');
        $composite->writeImage($output);

        foreach ($images as $img) {
            $img->clear();
            $img->destroy();
        }
        foreach ($imagePaths as $path) {
            unlink($path);
        }
        $composite->clear();
        $composite->destroy();

        return $output;
    }
}
