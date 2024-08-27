<?php

namespace Nhattuanbl\LaraMedia\Livewire;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Nhattuanbl\LaraMedia\Models\LaraMedia;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Livewire\Component;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MediaTable extends Component
{
    public string $model = LaraMedia::class;
    public ?string $search = null;

    public function render(): View|Factory|Application
    {
        return view('lara-media::media-table');
    }

    public function datatable()
    {
        return 'hehe';
    }
}
