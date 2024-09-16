<?php

namespace Nhattuanbl\LaraMedia\Enums;

enum FileTypeEnum: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';
    case Archive = 'archive';

    public static function fromMimeType(string $mimeType): ?self
    {
        if (str_starts_with($mimeType, 'image/')) {
            return self::Image;
        } elseif (str_starts_with($mimeType, 'video/')) {
            return self::Video;
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return self::Audio;
        } elseif (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
        ])) {
            return self::Document;
        } elseif (in_array($mimeType, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-tar',
            'application/gzip',
        ])) {
            return self::Archive;
        }

        return null;
    }
}
