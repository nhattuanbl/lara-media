<?php

namespace Nhattuanbl\LaraMedia\Services;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Log;
use Mimey\MimeTypes;
use Nhattuanbl\LaraHelper\Helpers\StringHelper;
use Nhattuanbl\LaraMedia\Contracts\FileAttr;
use Nhattuanbl\LaraMedia\Enums\PositionEnum;
use Nhattuanbl\LaraMedia\Enums\ResolutionEnum;
use Nhattuanbl\LaraMedia\Exceptions\DownloadFailedException;
use Nhattuanbl\LaraMedia\Exceptions\ReadFailedException;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Validator;

class LaraMediaService
{
    CONST ALBUM_TEMP = 'TEMP';

    public string $disk;
    public string $model;
    public array $responsive = [];
    public string $queue_name;
    public string $queue_connection;
    public ?string $album = null;
    public bool $deleteOriginal;
    public int $limit = 1;
    public int $thumbnail = 0;
    public ?int $thumbnailWidth = null;
    public array $compositeThumbnail = [];
    public ?string $watermark_path = null;
    public int $watermark_size;
    public int|float $watermark_opacity;
    public PositionEnum $watermark_position;
    public array $acceptsMimeTypes = [];
    public ?string $format = null;
    public int $quality = 100;

    /** @var ResolutionEnum[] */
    public array $resolution;

    public function __construct()
    {
        $this->disk = config('lara-media.disk');
        $this->model = config('lara-media.model');
        $this->queue_name = config('lara-media.conversion.queue_name');
        $this->queue_connection = config('lara-media.conversion.queue_connection');
        $this->deleteOriginal = config('lara-media.conversion.delete_original');
        $this->watermark_position = PositionEnum::Center;
        $this->watermark_size = 40;
        $this->watermark_opacity = 0.2;
    }

    public function onQueue(string $name, ?string $connection = null): self
    {
        $this->queue_name = $name;
        $this->queue_connection = $connection ?? config('lara-media.conversion.queue_connection');
        return $this;
    }

    public function toAlbum(string $album): self
    {
        $this->album = $album;
        return $this;
    }

    public function disk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    public function deleteOriginal(): self
    {
        $this->deleteOriginal = true;
        return $this;
    }

    public function thumbnail(int $interval = 1, ?int $width = null, array $composite = []): self
    {
        $this->thumbnail = $interval;
        $this->thumbnailWidth = $width;
        $this->compositeThumbnail = $composite;
        return $this;
    }

    public function watermark(string $path, PositionEnum $position = PositionEnum::Center, int $size = 40, int|float $opacity = 0.2): self
    {
        $this->watermark_path = $path;
        $this->watermark_position = $position;
        $this->watermark_opacity = $opacity;
        $this->watermark_size = $size;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = ($limit <= 0) ? 1 : $limit;
        return $this;
    }

    public function acceptsMimeTypes(array $mimes): self
    {
        $this->acceptsMimeTypes = $mimes;
        return $this;
    }

    public function toFormat(string $ext): self
    {
        $this->format = $ext;
        return $this;
    }

    public function optimize(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * @throws Exception
     */
    public static function getFileAttr(UploadedFile|LaraMedia|string $file, ?array $httpOptions = null): FileAttr
    {
        if (is_string($file)) {
            $temporaryFile = tempnam(sys_get_temp_dir(), 'lara-media');
            $base64Pattern = '/^data:([a-zA-Z0-9\/\-\+]+);base64,([A-Za-z0-9+\/=]+)$/';

            $validator = Validator::make(['url' => $file], ['url' => 'required|url']);
            if (!$validator->fails()) {
                Log::channel('lara-media')->debug('Add Media from URL', ['url' => $file]);

                $response = Http::withOptions($httpOptions ?? [
                    RequestOptions::HTTP_ERRORS => false,
                    RequestOptions::VERIFY => false,
                    RequestOptions::SINK => $temporaryFile,
                    RequestOptions::ALLOW_REDIRECTS => true,
                ])->get($file);

                if (!$response->successful()) {
                    throw new DownloadFailedException($file, $response->status());
                }

                $headers = $response->headers();
                $contentDisposition = isset($headers['Content-Disposition']) ? $headers['Content-Disposition'][0] : '';
                if (preg_match('/filename="?([^"\s]+)"?/', $contentDisposition, $matches)) {
                    $filename = $matches[1] ?? null;
                }

                if (!isset($filename)) {
                    $urlPath = parse_url($file, PHP_URL_PATH);
                    $filename = basename($urlPath);
                }

                $name = pathinfo($filename, PATHINFO_FILENAME);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $mimeType = isset($headers['Content-Type']) ? $headers['Content-Type'][0] : null;
                if (!$mimeType) {
                    $mimeType = (new MimeTypes)->getMimeType($ext);
                }

                return new FileAttr($temporaryFile, $name, $ext, $mimeType, filesize($temporaryFile), true);
            } else if (preg_match($base64Pattern, $file, $matches)) {
                Log::channel('lara-media')->debug('Add Media from Base64', ['length' => strlen($file)]);
                $name = StringHelper::randLow();
                $mimeType = $matches[1];
                file_put_contents($temporaryFile, base64_decode($matches[2]));

                return new FileAttr($temporaryFile, $name, (new MimeTypes)->getExtension($mimeType), $mimeType, filesize($temporaryFile), true);
            } else if (file_exists($file)) {
                Log::channel('lara-media')->debug('Add Media from File', ['file' => $file]);

                $name = pathinfo($file, PATHINFO_FILENAME);
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $mimeType = (new MimeTypes)->getMimeType($ext);

                return new FileAttr($file, $name, $ext, $mimeType, filesize($file));
            }

            throw new ReadFailedException($file);
        } else if ($file instanceof LaraMedia) {
            Log::channel('lara-media')->debug('Add Media from LaraMedia Model', ['id' => $file->id]);

            if (!Storage::disk($file->disk)->exists($file->path)) {
                throw new ReadFailedException($file->path);
            }

            return new FileAttr($file->path, $file->name, $file->ext, $file->mime_type, $file->size);
        }

        Log::channel('lara-media')->debug('Add Media from UploadedFile', ['name' => $file->getClientOriginalName()]);
        return new FileAttr($file->getPathname(), $file->getClientOriginalName(), $file->getClientOriginalExtension(), $file->getClientMimeType(), $file->getSize());
    }

    public static function sanitizeFilename(string $filename): string
    {
        $invalidChars = [
            '\\', '/', ':', '*', '?', '"', '<', '>', '|', // Windows invalid characters
            "\0", "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", // Control chars (0-8)
            "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F", "\x10", "\x11", "\x12", // Control chars (9-18)
            "\x13", "\x14", "\x15", "\x16", "\x17", "\x18", "\x19", "\x1A", "\x1B", "\x1C", // Control chars (19-28)
            "\x1D", "\x1E", "\x1F" // Control chars (29-31)
        ];

        $sanitized = str_replace($invalidChars, '_', $filename);
        $sanitized = trim($sanitized, " \t\n\r\0\x0B.");

        if (strlen($sanitized) > 0) {
            return $sanitized;
        }

        return StringHelper::randLow();
    }
}
