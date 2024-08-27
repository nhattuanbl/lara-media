<?php

namespace Nhattuanbl\LaraMedia\Livewire;

use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Nhattuanbl\LaraHelper\Helpers\FileHelper;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Nhattuanbl\LaraMedia\Services\LaraMediaService;

#[Lazy]
class StorageReport extends Component
{
    public string $sum_total_size = '0';
    public string $sum_total_unit = 'B';
    public array $xAxis;
    public array $capacityChart;
    public array $filesChart;

    /** @var \Illuminate\Database\Eloquent\Collection */
    public $albums;
    public string $viewType = 'month';

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
        if ($this->viewType === 'day') {
            $limit = Carbon::now()->daysInMonth;
            $groupBy = [
                'day' => ['$dayOfMonth' => ['$toDate' => '$created_at']],
            ];
            $sort = [
                'day' => -1,
            ];
            $select = [

            ];
        } else {
            $limit = 12;
            $groupBy = [
                'year' => ['$year' => ['$toDate' => '$created_at']],
                'month' => ['$month' => ['$toDate' => '$created_at']],
            ];
            $sort = [
                'year' => -1,
                'month' => -1,
            ];
            $select = [
                'year' => '$_id.year',
                'month' => '$_id.month',
                'sum_total_files' => 1,
                'sum_total_size' => 1,
                '_id' => 0,
            ];
        }

        if ($this->isMongoConnection()) {
            $this->albums = LaraMedia::raw(function($collection) use ($limit, $groupBy, $sort, $select) {
                return $collection->aggregate([
                    [
                        '$match' => [
                            'album' => ['$ne' => LaraMediaService::ALBUM_TEMP],
                        ],
                    ],
                    [
                        '$group' => [
                            '_id' => $groupBy,
                            'sum_total_files' => ['$sum' => '$total_files'],
                            'sum_total_size' => ['$sum' => '$total_size'],
                        ],
                    ],
                    [
                        '$project' => $select,
                    ],
                    [
                        '$sort' => $sort,
                    ],
                    [
                        '$limit' => $limit,
                    ],
                ]);
            });
        } else {
            $this->albums = LaraMedia::selectRaw(
                'YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_files) as sum_total_files, SUM(total_size) as sum_total_size'
            )
                ->where('album', '!=', LaraMediaService::ALBUM_TEMP)
                ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get();
        }

        dd($this->albums);

        $sum_total_size = explode(' ', FileHelper::byte2Readable($this->albums->sum('sum_total_size')));
        $this->sum_total_size = $sum_total_size[0];
        $this->sum_total_unit = mb_strtoupper($sum_total_size[1]);

        $this->xAxis = [];
        foreach ($this->albums->reverse()->values() as $a) {
            $this->xAxis[] = Carbon::createFromFormat('m', $a->month)->format('M') . ($a->month == 1 ? ' ' . $a->year : '');
        }

        $this->capacityChart = [];
        $this->filesChart = [];
        foreach ($this->albums->reverse()->values() as $a) {
            $this->capacityChart[] = number_format($a->sum_total_size / 1048576, 2);
            $this->filesChart[] = $a->sum_total_files;
        }
    }

    public function viewDay(): void
    {
        $this->viewType = 'day';
        $this->setMetrics();
    }

    public function viewMonth(): void
    {
        $this->viewType = 'month';
        $this->setMetrics();
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
