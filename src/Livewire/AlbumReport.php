<?php

namespace Nhattuanbl\LaraMedia\Livewire;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Nhattuanbl\LaraMedia\Models\LaraMedia;

#[Lazy]
class AlbumReport extends Component
{
    public int $sumFiles = 0;
    public int $sumVersions = 0;
    public int $sumTotalFiles = 0;
    public array $chartData = [];
    public int $sumTotalSize = 0;

    /** @var \Illuminate\Database\Eloquent\Collection */
    public $albums;

    public function render(): View|Factory|Application
    {
        return view('lara-media::album-report');
    }

    public function mount(): void
    {
        $this->setMetrics();
    }

    public function shitLazy()
    {
        $this->dispatch('lazyLoaded');
    }

    public function setMetrics(): void
    {
        $mediaModel = (config('lara-media.model'));
        if ($this->isMongoConnection()) {
            $this->albums = $mediaModel::raw(function($collection) {
                return $collection->aggregate([
//                    [
//                        '$match' => [
//                            'album' => ['$ne' => LaraMediaService::ALBUM_TEMP],
//                        ],
//                    ],
                    [
                        '$group' => [
                            '_id' => ['album' => '$album'],
                            'sum_files' => ['$sum' => 1],
                            'sum_total_files' => ['$sum' => '$total_files'],
                            'sum_total_size' => ['$sum' => '$total_size'],
                        ],
                    ],
                    [
                        '$project' => [
                            'album' => '$_id.album',
                            'sum_files' => 1,
                            'sum_total_files' => 1,
                            'sum_total_size' => 1,
                            '_id' => 0,
                        ],
                    ],
                    [
                        '$sort' => [
                            'sum_total_files' => -1,
                        ],
                    ],
                ]);
            });
        } else {
            $this->albums = $mediaModel::selectRaw('album, COUNT(*) as sum_files, SUM(total_files) as sum_total_files, SUM(total_size) as sum_total_size')
                ->groupBy('album')
                ->orderBy('sum_total_files', 'desc')
                ->get();
        }

        $this->sumTotalSize = $this->albums->sum('sum_total_size');
        $this->sumTotalFiles = $this->albums->sum('sum_total_files');
        $this->sumFiles = $this->albums->sum('sum_files');
        $this->sumVersions = $this->sumTotalFiles - $this->sumFiles;
        $this->chartData = $this->albums->map(fn($i) => [
            'category' => mb_ucfirst($i->album),
            'value' => $i->sum_total_files
        ])->toArray();
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="col-xxl-6 mb-5 mb-xl-10">
            <div class="card card-flush h-xl-100">
                <div class="card-header py-7">
                    <div class="m-0">
                        <div class="d-flex align-items-center mb-2 placeholder-wave">
                            <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1 ls-n2 placeholder rounded w-200px"></span>
                            <span class="badge badge-light-danger fs-base placeholder rounded w-100px">&nbsp;</span>
                        </div>
                        <span class="fs-6 fw-semibold text-gray-500">Albums</span>
                    </div>
                </div>
                <div class="overlay overlay-block card-body pt-0 pb-1 placeholder-wave d-flex justify-content-center align-items-center">
                    <div id="albumChart" class="h-300px w-300px rounded-circle placeholder opacity-25"></div>
                        <div class="overlay-wrapper">
                            <div class="overlay-layer card-rounded bg-dark bg-opacity-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    private function isMongoConnection(): bool
    {
        return config('database.connections.' . config('lara-media.connection') . '.driver') === 'mongodb';
    }
}
