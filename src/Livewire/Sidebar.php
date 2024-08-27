<?php

namespace Nhattuanbl\LaraMedia\Livewire;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    public function render(): View|Factory|Application
    {
        return view('lara-media::sidebar');
    }
}
