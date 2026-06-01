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
    $normalised = [];
    foreach ($initialRows as $row) {
        if (is_array($row) && isset($row['limit'], $row['rate'])) {
            $normalised[] = $row;
        } elseif (is_array($row) && count($row) >= 2) {
            $normalised[] = ['limit' => $row[0], 'rate' => $row[1]];
        } else {
            $normalised[] = ['limit' => '', 'rate' => ''];
        }
    }
    $this->rows = empty($normalised) ? [['limit' => '', 'rate' => '']] : $normalised;
}

public function addRow(): void
{
    $this->rows[] = ['limit' => '', 'rate' => ''];
    $this->dispatch('repeaterUpdated', $this->rows);
}

public function removeRow(int $index): void
{
    unset($this->rows[$index]);
    $this->rows = array_values($this->rows);
    if (empty($this->rows)) {
        $this->rows = [['limit' => '', 'rate' => '']];
    }
    $this->dispatch('repeaterUpdated', $this->rows);
}


public function updatedRows($value)
{
    \Log::debug('Repeater rows updated', ['rows' => $value]);
    $this->dispatch('repeaterUpdated', $value);
}




    public function render()
    {
        return view('qf::livewire.custom.tax-bands-repeater');

    }
}