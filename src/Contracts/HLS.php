<?php

namespace Nhattuanbl\LaraMedia\Contracts;

use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video;

class HLS extends X264
{
    public function getExtraParams(): array
    {
        return ['-hls_time', '10', '-hls_playlist_type', 'vod'];
    }

    public function getAvailableVideoCodecs(): array
    {
        return ['libx264'];
    }

    public function save(Video $video, string $outputPath): void
    {
        $video->save($this, $outputPath);
    }
}
