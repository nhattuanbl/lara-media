<?php

namespace Nhattuanbl\LaraMedia\Listeners;

use Exception;
use Illuminate\Support\Facades\Storage;
use Log;
use Nhattuanbl\LaraMedia\Events\MediaDeleted;
use Nhattuanbl\LaraMedia\Models\LaraMedia;

class DeleteMediaVersions
{
    /**
     * @throws Exception
     */
    public function handle(MediaDeleted $event): void
    {
        $media = $event->media;
        $version = $event->version;
        $conversion = $media->conversions;
        $responsive = $media->responsive;
        $properties = $media->properties;

        $paths = [];
        if ($version === 'original') {
            $paths[] = [
                'version' => $version,
                'disk' => $media->disk,
                'path' => Storage::disk($media->disk)->path($media->path . DIRECTORY_SEPARATOR . $media->name . '.' . $media->ext),
            ];
        } else if ($version) {
            $paths[] = [
                'version' => $version,
                'disk' => $properties['conversion_disk'],
                'path' => $media->getVersionPath($version),
            ];
        } else {
            $paths = $media->version_paths;
        }

        Log::channel('lara-media')->warning('Delete media #' . $media->id, $paths);
        foreach ($paths as $p) {
            Storage::disk($p['disk'])->delete($p['path']);

            if ($p['version'] === 'original') {
                $size = $media->size;
                $media->is_removed = true;
            } else {
                $size = in_array($p['version'], array_keys($conversion))
                    ? $conversion[$p['version']]['size']
                    : $responsive[$p['version']]['size'];
                if (in_array($p['version'], array_keys($conversion))) {
                    unset($conversion[$p['version']]);
                } else {
                    unset($responsive[$p['version']]);
                }
            }

            $media->total_files--;
            $media->total_size -= $size;
        }

        if (empty($conversion) && empty($responsive)) {
            unset($properties['conversion_disk']);
            unset($properties['took']);
            if (isset($properties['ext'])) unset($properties['ext']);
        }

        if ($version) {
            $media->responsive = $responsive;
            $media->conversions = $conversion;
            $media->properties = $properties;
            $media->save();
        }
    }
}
