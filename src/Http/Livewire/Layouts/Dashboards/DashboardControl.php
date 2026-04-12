<?php

namespace QuickerFaster\LaravelUI\Http\Livewire\Layouts\Dashboards;

use Livewire\Component;

class DashboardControl extends Component
{
    public $timeDuration = "this_month";

    public function updatedTimeDuration()
    {
        $this->dispatch('timeDurationChanged', $this->timeDuration);
    }

    public function render()
    {
        return view('dashboard::components.layouts.dashboards.dashboard-control');
    }
}