<?php

namespace Nhattuanbl\LaraMedia\Contracts;

use Exception;
use Nhattuanbl\LaraMedia\Enums\PositionEnum;

class PhotoRegister extends MediaRegister
{
    public string $format = 'webp';

    public ?string $watermarkPath = null;
    public PositionEnum $watermarkPosition;
    public float|int $watermarkOpacity;
    public int $watermarkHeight;
    public array $acceptsMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/tiff',
        'image/webp',
        'image/svg+xml',
        'image/ico',
        'image/psd',
        'image/x-xbitmap',
        'image/x-xpixmap',
        'image/x-portable-pixmap',
        'image/x-portable-bitmap',
        'image/x-portable-graymap',
        'image/x-targa',
        'image/x-tga',
        'image/x-cmu-raster',
        'image/x-ms-bmp',
        'image/x-pcx',
        'image/x-pict',
        'image/x-xcf',
        'image/x-xwindowdump',
    ];

    public function responsive(int|array $responsive = []): self
    {
        $this->responsive = is_array($responsive) ? $responsive : [$responsive];
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
}
