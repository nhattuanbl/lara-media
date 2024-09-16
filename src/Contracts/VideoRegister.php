<?php

namespace Nhattuanbl\LaraMedia\Contracts;

use AllowDynamicProperties;
use Exception;
use Nhattuanbl\LaraMedia\Enums\PositionEnum;
use Nhattuanbl\LaraMedia\Enums\ResolutionEnum;

class VideoRegister extends MediaRegister
{
    public string $format = 'mp4';
    public ?int $quality = 55;

    public int $thumbnailRow = 0;
    public int $thumbnailCol = 0;
    public int $posters = 0;

    public ?int $posterWidth = null;
    public ?string $watermarkPath = null;
    public PositionEnum $watermarkPosition;
    public float|int $watermarkOpacity;
    public int $watermarkHeight;
    public bool $twoPass = false;

    public array $acceptsMimeTypes = [
        'video/3gpp',
        'video/3gpp2',
        'video/annodex',
        'video/divx',
        'video/flv',
        'video/h264',
        'video/mp4',
        'video/mpeg',
        'video/ogg',
        'video/quicktime',
        'video/webm',
        'video/x-f4v',
        'video/x-fli',
        'video/x-flv',
        'video/x-m4v',
        'video/x-matroska',
        'video/x-mjpeg',
        'video/x-ms-asf',
        'video/x-ms-wmv',
        'video/x-msvideo',
        'video/x-theora',
        'video/x-vob',
        'video/x-vp8',
        'video/x-vp9',
        'video/x-xvid',
    ];

    public string $encryptKey;

    public function __construct()
    {
        parent::__construct();
        $this->twoPass = (bool) config('lara-media.video.2pass_encoding', false);
    }

    /**
     * @throws Exception
     */
    public function format(string $format): self
    {
        if (!in_array($format, array_keys(config('lara-media.video.encoding')))) {
            throw new Exception('Unknown format encoding ' . $format);
        }

        $this->format = $format;
        return $this;
    }

    public function responsive(ResolutionEnum|array $responsive = []): self
    {
        $this->responsive = is_array($responsive) ? $responsive : [$responsive];
        return $this;
    }

    public function thumbnail(int $row = 4, int $column = 4): self
    {
        $this->thumbnailCol = $column;
        $this->thumbnailRow = $row;
        return $this;
    }

    public function posters(int $posters = 1, ?int $width = null): self
    {
        $this->posters = $posters;
        $this->posterWidth = $width;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function watermark(?string $path = null, ?PositionEnum $position = null, null|float|int $opacity = null, ?int $heightPercent = null): self
    {
        $path = $path ?? config('lara-media.watermark.path');
        if (!file_exists($path)) {
            throw new Exception('Watermark file not found');
        }

        $this->watermarkPosition = $position ?? config('lara-media.watermark.position');
        $this->watermarkOpacity = $opacity ?? config('lara-media.watermark.opacity');
        $this->watermarkHeight = $heightPercent ?? config('lara-media.watermark.height_percent');
        $this->watermarkPath = $path;

        return $this;
    }

    public function setEncryptKey(string $key): self
    {
        $this->encryptKey = $key;
        return $this;
    }
}
