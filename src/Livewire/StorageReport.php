<?php

namespace Nhattuanbl\LaraMedia\Livewire;

use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use MongoDB\BSON\UTCDateTime;
use Nhattuanbl\LaraMedia\Contracts\FileAttr;
use Nhattuanbl\LaraMedia\Models\LaraMedia;

class StorageReport extends Component
{
    public string $sum_total_size = '0';
    public string $sum_total_unit = 'B';
    public string $total_size_by_filter;
    public array $xAxis;
    public array $capacityChart;
    public array $filesChart;
    public string $subTitle = '';
    public array $tooltipTitle = [];

    /** @var Collection */
    public $albums;
    public string $viewType = 'months';

    public function render(): View|Factory|Application
    {
        return view('lara-media::storage-report');
    }

    public function shitLazy(): void
    {
        $this->dispatch('lazyLoaded');
    }

    public function mount(): void
    {
        $this->setMetrics();
    }

    public function setMetrics(): void
    {
        $match = [];
        if ($this->viewType === 'days') {
            $limit = 30;
            $dateFilter = Carbon::now()->subDays($limit)->startOfDay()->getTimestamp() * 1000;
            $groupBy = [
                'day' => ['$dayOfMonth' => ['$toDate' => '$created_at']],
                'month' => ['$month' => ['$toDate' => '$created_at']],
                'year' => ['$year' => ['$toDate' => '$created_at']],
            ];
        } else {
            $limit = 12;
            $dateFilter = Carbon::now()->subMonths($limit)->startOfDay()->getTimestamp() * 1000;
            $groupBy = [
                'month' => ['$month' => ['$toDate' => '$created_at']],
                'year' => ['$year' => ['$toDate' => '$created_at']],
            ];
        }

        $mediaModel = (config('lara-media.model'));
        if ($this->isMongoConnection()) {
            $this->albums = $mediaModel::raw(function($collection) use ($limit, $groupBy, $match, $dateFilter) {
                return $collection->aggregate([
                    ...(!empty($match) ? [['$match' => $match]] : []),
                    [
                        '$match' => [
                            'created_at' => [
                                '$gte' => new UTCDateTime($dateFilter),
                            ],
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => $groupBy,
                            'sum_total_files' => ['$sum' => '$total_files'],
                            'sum_total_size' => ['$sum' => '$total_size'],
                        ],
                    ],
                    [
                        '$project' => [
                            'year' => '$_id.year',
                            'month' => '$_id.month',
                            'day' => '$_id.day',
                            'sum_total_files' => 1,
                            'sum_total_size' => 1,
                            '_id' => 0,
                        ],
                    ],
                    [
                        '$sort' => [
                            'year' => -1,
                            'month' => -1,
                            'day' => -1,
                        ]
                    ],
                    ['$limit' => $limit],
                ]);
            });
        } else {
            $this->albums = $mediaModel::selectRaw("
                YEAR(created_at) as year,
                MONTH(created_at) as month" . ($this->viewType === 'days' ? ", DAY(created_at) as day" : "") . ",
                SUM(total_files) as sum_total_files,
                SUM(total_size) as sum_total_size
            ")
                ->where('created_at', '>=', $dateFilter)
                ->groupBy('year', 'month' . ($this->viewType === 'days' ? ', day' : ''))
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->when($this->viewType === 'days', fn($q) => $q->orderBy('day', 'desc'))
                ->limit($limit)
            ->get();
        }

        $this->subTitle = 'Storage usage in the last ' . $limit . ' ' . $this->viewType;
        $sum_total_size = $mediaModel::whereNot('album', LaraMedia::ALBUM_TEMP)->sum('total_size');
        $sum_total_size = explode(' ', FileAttr::byte2Readable($sum_total_size));
        $this->sum_total_size = $sum_total_size[0];
        $this->sum_total_unit = $sum_total_size[1];
        $this->total_size_by_filter = FileAttr::byte2Readable($this->albums->sum('sum_total_size'));

        $this->xAxis = [];
        $this->capacityChart = [];
        $this->filesChart = [];
        $this->tooltipTitle = [];
        $reversedAlbums = $this->albums->reverse();

        foreach ($reversedAlbums as $k => $a) {
            $this->capacityChart[] = number_format($a->sum_total_size / 1048576, 2);
            $this->filesChart[] = $a->sum_total_files;
            $date = Carbon::create($a->year, $a->month, $a->day ?? 1);
            if ($this->viewType === 'days') {
                if ($k === 0 || $k === $reversedAlbums->count() - 1) $this->xAxis[] = $date->format('M d');
                else $this->xAxis[] = $date->format('d');
                $this->tooltipTitle[] = $date->format('M d');
            } else {
                if ($k === 0 || $k === $reversedAlbums->count() - 1) $this->xAxis[] = $date->format('M') . ' ' . $a->year;
                else $this->xAxis[] = $date->format('M');
                $this->tooltipTitle[] = $date->format('M Y');
            }
        }

    }

    public function viewDay(): void
    {
        $this->viewType = 'days';
        $this->setMetrics();
        $this->dispatch('refresh', $this->tooltipTitle, $this->capacityChart, $this->filesChart, $this->xAxis);
    }

    public function viewMonth(): void
    {
        $this->viewType = 'months';
        $this->setMetrics();
        $this->dispatch('refresh', $this->tooltipTitle, $this->capacityChart, $this->filesChart, $this->xAxis);
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
                        <span class="fs-6 fw-semibold text-gray-500">Capacity</span>
                    </div>
                </div>
                <div class="overlay overlay-block card-body pt-0 pb-1 placeholder-wave d-flex flex-column">
                    <div class="rounded w-300px h-30px placeholder opacity-25 my-5"></div>
                    <div class="rounded w-350px h-30px placeholder opacity-25 my-5"></div>
                    <div class="rounded w-200px h-30px placeholder opacity-25 my-5"></div>
                    <div class="rounded w-300px h-30px placeholder opacity-25 my-5"></div>
                    <div class="rounded w-100px h-30px placeholder opacity-25 my-5"></div>
                    <div class="rounded w-350px h-30px placeholder opacity-25 my-5 mb-15"></div>
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
