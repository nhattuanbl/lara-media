<?php

namespace Nhattuanbl\LaraMedia\Livewire;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Imtigger\LaravelJobStatus\JobStatus;
use Livewire\Component;
use Nhattuanbl\LaraMedia\Models\LaraMedia;

class ASide extends Component
{
    public Collection $jobList;
    public int $countProcessing = 0;
    public int $countPending = 0;
    public int $countFailed = 0;
    public int $countTotal = 0;

    public function render(): View|Factory|Application
    {
        $mediaModel = (config('lara-media.model'));
        $jobModel = (config('job-status.model'));

        $this->countTotal = $jobModel::where('model_type', $mediaModel)->count();
        $this->countProcessing = $jobModel::where('model_type', $mediaModel)
            ->where('status', JobStatus::STATUS_EXECUTING)->count();
        $this->countPending = $jobModel::where('model_type', $mediaModel)
            ->where('status', JobStatus::STATUS_QUEUED)->count();
        $this->countFailed = $jobModel::where('model_type', $mediaModel)
            ->where('status', JobStatus::STATUS_FAILED)->count();

        $this->jobList = $mediaModel::orderBy('updated_at', 'desc')
            ->whereHas('status')
            ->limit(20)
            ->get();
        $this->jobList->load('status');
        $this->jobList = $this->jobList->sortByDesc('status.updated_at');

        return view('lara-media::a-side')
            ->with('username', Auth::check() ? Auth::user()->name : 'Guest');
    }
}
