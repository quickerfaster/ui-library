<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;

class BackgroundJobsPanel extends Component
{
    public $activeTab = 'exports'; // 'exports' or 'imports'

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('qf::livewire.background-jobs-panel');
    }
}