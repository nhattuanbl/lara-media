<?php

namespace Nhattuanbl\LaraMedia\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Nhattuanbl\LaraMedia\Events\MediaDeleted;

/**
 * @property string id
 * @property string model_type
 * @property string model_id
 * @property string album
 * @property string disk
 * @property string path
 * @property string name
 * @property string ext
 * @property string mime_type
 * @property int size
 * @property string hash
 * @property array properties
 * @property array conversions
 * @property array responsive
 * @property bool is_removed
 * @property int total_size
 * @property int total_files
 * @property string description
 * @property-read \Illuminate\Database\Eloquent\Model model
 * @property-read \Illuminate\Database\Eloquent\Model status
 * @property-read bool is_audio
 * @property-read bool is_video
 * @property-read bool is_image
 *
 * @property-read bool is_temporary
 * @property-read string url
 * @property-read array urls
 * @property-read string preview
 * @property-read string version_path
 * @property-read array version_paths
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin Collection
 */
class LaraMedia extends Model
{
    CONST ALBUM_TEMP = 'TEMPORARY';
    public function __construct(array $attributes = [])
    {
        $this->table = config('lara-media.table_name');
        parent::__construct($attributes);
    }

    protected $fillable = [
        'model_type',
        'model_id',
        'album',
        'disk',
        'path',
        'name',
        'ext',
        'mime_type',
        'size',
        'hash',
        'properties',
        'conversion_disk',
        'conversions',
        'responsive',
        'is_removed',
        'total_size',
        'total_files',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'properties' => 'array',
            'conversions' => 'array',
            'responsive' => 'array',
            'is_removed' => 'bool',
            'total_size' => 'int',
            'total_files' => 'int',
        ];
    }

    protected $dispatchesEvents = [
        'deleting' => MediaDeleted::class,
    ];

    public function getConnectionName()
    {
        return config('lara-media.connection');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function status(): MorphOne
    {
        return $this->morphOne(config('job-status.model'), 'model');
    }

    public function getKeyAttribute($value = null): string
    {
        return $this->{$this->getKeyName()};
    }

    public function getIsTemporaryAttribute(): bool
    {
        return $this->album === self::ALBUM_TEMP;
    }

    public function getIsAudioAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    public function getIsVideoAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * @throws Exception
     */
    public function getUrlAttribute(null|int|string $key = null): ?string
    {
        $url = null;
        if (!$key) {
            if ($this->is_removed) {
                return null;
            }

            $path = $this->path . '/' . $this->name . '.' . $this->ext;
            $url = Storage::disk($this->disk)->url($path);
        } else if (isset($this->conversions[$key]) || isset($this->responsive[$key])) {
            $path = $this->getVersionPath($key);
            $url = Storage::disk($this->properties['conversion_disk'])->url($path);
        }

        if (!isset($url)) {
            return null;
        }

        if (str_starts_with($url, 'http')) {
            return $url . '?v=' . $this->updated_at->timestamp;
        }

        $url = Storage::disk('lara-media')->url($path);
        return url($url . '?v=' . $this->updated_at->timestamp);
    }

    /**
     * @throws Exception
     */
    public function getUrlsAttribute(): array
    {
        $result = [];
        $result[] = [
            'version' => 'original',
            'url' => $this->getUrlAttribute()
        ];

        if (is_array($this->conversions)) {
            foreach ($this->conversions as $widthOrFormat => $detail) {
                $result[] = ['version' => $widthOrFormat, 'url' => $this->getUrlAttribute($widthOrFormat)];
            }
        }

        if (is_array($this->responsive)) {
            foreach ($this->responsive as $width => $detail) {
                $result[] = ['version' => $width, 'url' => $this->getUrlAttribute($width)];
            }
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getPreviewAttribute(): ?string
    {
        if ($this->is_video) {
            return $this->getUrlAttribute(5);
        } else if ($this->is_image && !empty($this->responsive)) {
            return $this->getUrlAttribute(array_key_first($this->responsive));
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getVersionPath(string|int $version): string
    {
        if (!isset($this->conversions[$version]) && !isset($this->responsive[$version])) {
            throw new Exception('Version '.$version.' not found #' . $this->_id);
        }

        $path = $this->path . '/' . $this->name;
        if ($this->is_audio) {
            $path .= '_converted.' . $version;
        } else if (isset($this->conversions[$version]) || $this->is_image){
            $path .= '_converted_' . $version . '.' . $this->properties['ext'];
        } else {
            $path .= '_' . $version . '.jpg';
        }

        return $path;
    }

    /**
     * @throws Exception
     */
    public function getVersionPathsAttribute(): array
    {
        $result = [];
        $result[] = [
            'version' => 'original',
            'disk' => $this->disk,
            'path' => $this->path . '/' . $this->name . '.' . $this->ext
        ];

        if (is_array($this->conversions)) {
            foreach ($this->conversions as $widthOrFormat => $detail) {
                $result[] = [
                    'version' => $widthOrFormat,
                    'disk' => $this->properties['conversion_disk'],
                    'path' => $this->getVersionPath($widthOrFormat)
                ];
            }
        }

        if (is_array($this->responsive)) {
            foreach ($this->responsive as $width => $detail) {
                $result[] = [
                    'version' => $width,
                    'disk' => $this->properties['conversion_disk'],
                    'path' => $this->getVersionPath($width)
                ];
            }
        }

        return $result;
    }

    public function temporaryUrl(int $expired_at, string|int|null $version = null, array $data = []): string
    {
        $data = array_merge($data, [
            'id' => $this->id,
            'expired_at' => $expired_at,
            'version' => $version,
        ]);
        $route = config('lara-media.temporary.route_name');
        $string = json_encode($data);
        $cipher = "AES-128-CBC";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($string, $cipher, config('app.key'), OPENSSL_RAW_DATA, $iv);
        $encryptedString = base64_encode($iv . $encrypted);
        $signed = rtrim(strtr($encryptedString, '+/', '-_'), '=');
        return route($route, ['signature' => $signed]);
    }

    public static function validTemporaryUrl(null|string $signature): array|false
    {
        if (!$signature) {
            return false;
        }

        $encryptedString = str_pad(strtr($signature, '-_', '+/'), strlen($signature) % 4, '=');
        $data = base64_decode($encryptedString);
        $cipher = "AES-128-CBC";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $ivlen);
        $encrypted = substr($data, $ivlen);
        $json = openssl_decrypt($encrypted, $cipher, config('app.key'), OPENSSL_RAW_DATA, $iv);
        if ($json === false) {
            return false;
        }

        $ar = json_decode($json, true);
        if (time() >= $ar['expired_at']) {
            return false;
        }

        if (isset($ar['views']) && is_int($ar['views'])) {
            $views = Cache::increment('lara-media-temporary-' . md5($signature));
            if ($views > $ar['views']) {
                return false;
            }
            $ar['views_count'] = $views;
        }

        return $ar;
    }
}
