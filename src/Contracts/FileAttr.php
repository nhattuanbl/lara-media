<?php

namespace Nhattuanbl\LaraMedia\Contracts;

use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Mimey\MimeTypes;
use Nhattuanbl\LaraMedia\Enums\FileTypeEnum;
use Nhattuanbl\LaraMedia\Exceptions\DownloadFailedException;
use Nhattuanbl\LaraMedia\Exceptions\ReadFailedException;
use Nhattuanbl\LaraMedia\Models\LaraMedia;

class FileAttr
{
    public FileTypeEnum $fileType;
    public function __construct(
        public string $path,
        public string $name,
        public string $extension,
        public string $mime,
        public int $size,
        public bool $isTemporary = false,
        public ?string $id = null,
        public ?string $tempName = null,
    ) {
        $this->fileType = FileTypeEnum::fromMimeType($this->mime);
        Log::channel('lara-media')->debug('FileAttr', (array) $this);
    }

    public static function isLocalDisk(string $disk): bool
    {
        return config('filesystems.disks.'.$disk.'.driver') === 'local';
    }

    /**
     * @throws DownloadFailedException
     * @throws ConnectionException
     * @throws ReadFailedException
     */
    public static function upload(UploadedFile|LaraMedia|string $file, ?array $httpOptions = null): self
    {
        if (is_string($file)) {
            $temporaryFile = tempnam(sys_get_temp_dir(), 'lara-media');
            $base64Pattern = '/^data:([a-zA-Z0-9\/\-\+]+);base64,([A-Za-z0-9+\/=]+)$/';

            $validator = Validator::make(['url' => $file], ['url' => 'required|url']);
            if (!$validator->fails()) {
                Log::channel('lara-media')->debug('Add Media from URL', ['url' => $file]);

                $response = Http::withOptions($httpOptions ?? [
                    RequestOptions::HTTP_ERRORS     => false,
                    RequestOptions::VERIFY          => false,
                    RequestOptions::SINK            => $temporaryFile,
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

                $path = pathinfo($temporaryFile, PATHINFO_DIRNAME);
                $size = filesize($temporaryFile);
                $tempName = pathinfo($temporaryFile, PATHINFO_BASENAME);

                return new FileAttr($path, $name, $ext, $mimeType, $size, true, tempName: $tempName);
            } else if (preg_match($base64Pattern, $file, $matches)) {
                Log::channel('lara-media')->debug('Add Media from Base64', ['length' => strlen($file)]);
                $name = floor(microtime(true) * 1000);
                $mimeType = $matches[1];
                $path = pathinfo($temporaryFile, PATHINFO_DIRNAME);
                $tempName = pathinfo($temporaryFile, PATHINFO_BASENAME);
                file_put_contents($temporaryFile, base64_decode($matches[2]));

                return new FileAttr($path, $name, (new MimeTypes)->getExtension($mimeType), $mimeType, filesize($temporaryFile), true, tempName: $tempName);
            } else if (file_exists($file)) {
                Log::channel('lara-media')->debug('Add Media from File', ['file' => $file]);

                $name = pathinfo($file, PATHINFO_FILENAME);
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $path = pathinfo($file, PATHINFO_DIRNAME);
                $mimeType = (new MimeTypes)->getMimeType($ext);

                return new FileAttr($path, $name, $ext, $mimeType, filesize($file), false);
            }

            throw new ReadFailedException($file);
        } else if ($file instanceof LaraMedia) {
            Log::channel('lara-media')->debug('Add Media from LaraMedia Model #' . $file->id);

            $fullPath = $file->path . DIRECTORY_SEPARATOR . $file->name . '.' . $file->ext;
            if (!Storage::disk($file->disk)->exists($fullPath)) {
                throw new ReadFailedException('Unable to read file at [' . $file->disk . '] ' . $fullPath);
            }

            if (self::isLocalDisk($file->disk)) {
                $path = Storage::disk($file->disk)->path($fullPath);
                $path = pathinfo($path, PATHINFO_DIRNAME);
                $tempName = $file->name . '.' . $file->ext;
                $is_temporary = (bool)$file->is_temporary;
                $filesize = $file->size;
            } else {
                $temporaryFile = tempnam(sys_get_temp_dir(), 'lara-media');
                $resource = Storage::disk($file->disk)->readStream($fullPath);

                $temp = config('lara-media.conversion.temp', 'temp');
                if (!is_writable($temp)) {
                    throw new ReadFailedException('Temporary directory is not writable: ' . $temp);
                }

                $fp = fopen($temporaryFile, 'w');
                stream_copy_to_stream($resource, $fp);
                fclose($fp);
                $path = pathinfo($temporaryFile, PATHINFO_DIRNAME);
                $tempName = pathinfo($temporaryFile, PATHINFO_BASENAME);
                $is_temporary = true;
                $filesize = filesize($temporaryFile);
            }

            return new FileAttr($path, $file->properties['name'], $file->ext, $file->mime_type, $filesize, $is_temporary, $file->id, $tempName);
        }

        /** @var UploadedFile $file */
        Log::channel('lara-media')->debug('Add Media from UploadedFile', ['name' => $file->getClientOriginalName()]);
        return new FileAttr($file->getPath(), pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            $file->getClientOriginalExtension(),
            $file->getClientMimeType(),
            $file->getSize(),
            true,
            tempName: $file->getFilename()
        );
    }

    public static function sanitizeFilename(string $filename): string
    {
        $invalidChars = [
            '\\', '/', ':', '*', '?', '"', '<', '>', '|',
            "\0", "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08",
            "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F", "\x10", "\x11", "\x12",
            "\x13", "\x14", "\x15", "\x16", "\x17", "\x18", "\x19", "\x1A", "\x1B", "\x1C",
            "\x1D", "\x1E", "\x1F"
        ];

        $sanitized = str_replace($invalidChars, '_', $filename);
        $sanitized = trim($sanitized, " \t\n\r\0\x0B.");
        return strlen($sanitized) ? $sanitized : floor(microtime(true) * 1000);
    }

    public static function byte2Readable(int|float $size): string
    {
        if ($size === 0) {
            return '0 B';
        }

        $unit = ['B','KB','MB','GB','TB','PB'];
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2) . ' ' . $unit[$i];
    }
}
