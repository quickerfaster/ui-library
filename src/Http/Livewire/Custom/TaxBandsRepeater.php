<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Custom;

use Livewire\Component;

class TaxBandsRepeater extends Component
{
    public array $rows = [];
    public string $limitLabel = 'Limit (annual $)';
    public string $rateLabel = 'Rate (%)';

    public function mount(array $initialRows = [])
    {
        $this->rows = $initialRows ?: [['limit' => '', 'rate' => '']];
    }

    public function addRow(): void
    {
        $this->rows[] = ['limit' => '', 'rate' => ''];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        if (empty($this->rows)) {
            $this->rows = [['limit' => '', 'rate' => '']];
        }
    }

    public function render()
    {
        return view('qf::livewire.custom.tax-bands-repeater');

    }
}