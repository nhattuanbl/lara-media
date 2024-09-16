<?php

namespace Nhattuanbl\LaraMedia\Livewire;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Livewire\Attributes\Url;
use MongoDB\Collection;
use Nhattuanbl\LaraMedia\Events\MediaDeleted;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Livewire\Component;
use function Pest\Laravel\json;

class MediaTable extends Component
{
    public array $albumGroups = [];
    public array $extGroups = [];
    public array $diskGroups = [];
    public array $modelGroups = [];

    public function mount()
    {
        $mediaModel = (config('lara-media.model'));
        $this->albumGroups = $mediaModel::select('album')
            ->groupBy('album')
            ->orderBy('album')
            ->pluck('album')
            ->toArray();

        $this->extGroups = $mediaModel::select('ext')
            ->groupBy('ext')
            ->orderBy('ext')
            ->pluck('ext')
            ->toArray();

        $this->diskGroups = $mediaModel::select('disk')
            ->groupBy('disk')
            ->orderBy('disk')
            ->pluck('disk')
            ->toArray();

        $this->modelGroups = $mediaModel::select('model_type')
            ->groupBy('model_type')
            ->orderBy('model_type')
            ->pluck('model_type')
            ->map(function($i) {
                $ex = explode('\\', $i);
                return end($ex);
            })
            ->toArray();
    }

    public function render(): View|Factory|Application
    {
        return view('lara-media::media-table');
    }

    public function datatable(Request $request)
    {
        $limit = $request->query('limit', 15);
        $offset = $request->query('offset', 0);
        $search = $request->query('search');
        $sort_direction = $request->query('sort_direction', 'desc');
        $sort_column = $request->query('sort_column', 'created_at');
        $filterAlbum = $request->query('filterAlbum');
        $filterExt = $request->query('filterExt');
        $filterDisk = $request->query('filterDisk');
        $filterModel = $request->query('filterModel');

        $mediaModel = (config('lara-media.model'));
        $query = $mediaModel::whereNot('album', LaraMedia::ALBUM_TEMP)->orderBy($sort_column, $sort_direction);
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('properties', 'like', "%{$search}%")
                    ->orWhere('_id', $search)
                    ->orWhere('hash', $search)
                ;
            });
        }

        if ($filterAlbum !== 'Show All') {
            $query->where('album', $filterAlbum);
        }

        if ($filterExt !== 'Show All') {
            $query->where('ext', $filterExt);
        }

        if ($filterDisk !== 'Show All') {
            $query->where('disk', $filterDisk);
        }

        if ($filterModel !== 'Show All') {
            $query->where('model_type', 'like', "%{$filterModel}");
        }

        $result = $query->paginate($limit, ['*'], 'page', ($offset / $limit) + 1);
        $result->getCollection()->each->append(['preview', 'url', 'urls']);
        return $result;
    }

    public function delete(int|string $id)
    {
        /** @var LaraMedia $mediaModel */
        $mediaModel = (config('lara-media.model'));
        $version = request()->get('version');
        $media = $mediaModel::whereKey($id)->firstOrFail();
        if (!$version) {
            $media->delete();
        } else {
            event(new MediaDeleted($media, $version));
        }

        return response()->json(['id' => $id, 'version' => $version ?? null]);
    }

    private function isMongoConnection(): bool
    {
        return config('database.connections.' . config('lara-media.connection') . '.driver') === 'mongodb';
    }
}
