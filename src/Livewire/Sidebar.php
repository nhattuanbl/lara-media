<?php

namespace Nhattuanbl\LaraMedia\Livewire;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Nhattuanbl\LaraMedia\Models\LaraMedia;

class Sidebar extends Component
{
    public function clear()
    {
        $temp = LaraMedia::where('album', LaraMedia::ALBUM_TEMP)->get();
        foreach ($temp as $media) {
            $media->delete();
        }

        $this->dispatch('media:cleared', 'Clearing '.count($temp).' media ...');
    }

    public function render(): View|Factory|Application
    {
        return view('lara-media::sidebar');
    }
}
