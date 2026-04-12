<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;


class BottomBar extends Component
{
    public array $items = [];
    public int $maxVisible = 4;

    public function mount(array $items): void
    {
        $this->items = $items;
    }

    public function render()
    {
        return view('qf::livewire.navs.bottom-bar');
    }
}