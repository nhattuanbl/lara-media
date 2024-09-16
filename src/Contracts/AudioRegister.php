<?php

namespace Nhattuanbl\LaraMedia\Contracts;

use Exception;

class AudioRegister extends MediaRegister
{
    public array $acceptsMimeTypes = [
        'audio/aac',
        'audio/midi',
        'audio/x-midi',
        'audio/mpeg',
        'audio/ogg',
        'audio/opus',
        'audio/wav',
        'audio/webm',
        'audio/3gpp',
        'audio/3gpp2',
        'audio/x-flac',
        'audio/mp4',
        'audio/x-m4a',
        'audio/vnd.wave',
        'audio/x-ms-wma',
        'audio/x-pn-realaudio',
        'audio/x-aiff',
        'audio/x-wav',
        'audio/vnd.dlna.adts',
        'audio/x-ape',
        'audio/x-atrac',
        'audio/x-tta',
        'audio/x-alac',
        'audio/vnd.rn-realaudio',
        'audio/basic',
        'audio/vnd.qcelp',
        'audio/vnd.dts',
        'audio/vnd.dts.hd',
        'audio/vnd.dolby.dd-raw',
        'audio/vnd.everad.plj',
        'audio/vnd.lucent.voice',
        'audio/vnd.nuera.ecelp4800',
        'audio/vnd.nuera.ecelp7470',
        'audio/vnd.nuera.ecelp9600',
        'audio/vnd.sealedmedia.softseal.mpeg',
        'audio/vnd.sealedmedia.softseal.aac',
        'audio/vnd.digital-winds',
        'audio/vnd.dlna.adts',
        'audio/x-caf',
        'audio/x-scpls',
        'audio/x-matroska',
        'audio/x-ms-wma',
        'audio/x-wav',
        'audio/x-pn-realaudio-plugin',
        'audio/x-aac',
        'audio/vnd.rn-realaudio',
        'audio/mpegurl',
        'audio/mp3',
        'audio/x-mp3',
        'audio/vnd.rn-realaudio',
        'audio/vnd.audible.aax',
        'audio/vnd.audible.aa',
        'audio/vnd.dolby.heaac.1',
        'audio/vnd.dolby.heaac.2',
        'audio/vnd.dolby.dd-raw',
        'audio/vnd.dra',
        'audio/vnd.dts',
        'audio/vnd.dts.hd',
        'audio/vnd.lucent.voice',
        'audio/vnd.nuera.ecelp4800',
        'audio/vnd.nuera.ecelp7470',
        'audio/vnd.nuera.ecelp9600',
        'audio/vnd.sealedmedia.softseal.mpeg',
        'audio/x-flac',
        'audio/x-ms-wax',
        'audio/x-pn-realaudio',
        'audio/x-pn-wav',
        'audio/x-s3m',
        'audio/x-stm',
        'audio/x-voc',
        'audio/x-voxware',
        'audio/x-ttafile',
        'audio/x-alac',
        'audio/x-mod',
        'audio/x-nist',
        'audio/x-caf',
        'audio/x-pn-wav',
        'audio/x-matroska'
    ];

    /**
     * @throws Exception
     */
    public function format(string $format): self
    {
        $this->responsive($format);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function responsive(string|array $responsive = []): self
    {
        $supported = config('lara-media.audio.encoding');
        if (is_string($responsive)) {
            if (!in_array($responsive, array_keys($supported))) {
                throw new Exception('Unknown format encoding ' . $responsive);
            } else {
                $this->responsive = [$responsive];
            }
        } else {
            foreach ($responsive as $format) {
                if (!in_array($format, array_keys($supported))) {
                    throw new Exception('Unknown format encoding ' . $format);
                }
            }
            $this->responsive = $responsive;
        }

        return $this;
    }
}
