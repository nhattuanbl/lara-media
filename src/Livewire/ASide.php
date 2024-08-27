<?php

namespace Nhattuanbl\LaraMedia\Livewire;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ASide extends Component
{
    public function render(): View|Factory|Application
    {
        return view('lara-media::a-side')
            ->with('username', Auth::check() ? Auth::user()->name : 'Guest');
    }
}
