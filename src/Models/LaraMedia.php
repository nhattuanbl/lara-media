<?php

namespace Nhattuanbl\LaraMedia\Models;

use Illuminate\Support\Facades\Storage;
use Intervention\MimeSniffer\MimeSniffer;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Nhattuanbl\LaraMedia\Services\LaraMediaService;

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
 * @property-read bool is_temporary
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Collection
 */
class LaraMedia extends Model
{
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
        'conversions',
        'responsive',
        'is_removed',
        'total_size',
        'total_files',
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
//        'saved' => UserSaved::class,
//        'deleted' => UserDeleted::class,
    ];

    public function getConnectionName()
    {
        return config('lara-media.connection');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function getIsTemporaryAttribute(): bool
    {
        return $this->album === LaraMediaService::ALBUM_TEMP;
    }

    public function getUrlAttribute(?int $width = null): string
    {
        $sniffer = MimeSniffer::createFromFilename($this->name . '.' . $this->ext);
        if ($sniffer->isVideo()) {

        }
        if ($width && isset($this->responsive[$width])) {
            return Storage::disk($this->properties['responsive_disk'])
                ->url($this->path . '/' . $this->name . '_' . $width . '.' . $this->properties['responsive_ext']);
        }

        return Storage::disk($this->disk)->url($this->path . '/' . $this->name . '.' . $this->ext);
    }
}
