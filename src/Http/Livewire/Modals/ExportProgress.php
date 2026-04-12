<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\Export;

class ExportProgress extends Component
{
    public string $configKey;
    public ?int $exportId = null;
    public string $status = '';
    public ?string $error = null;
    public ?string $downloadUrl = null;
    public bool $exportStarted = false;

    protected $listeners = [
        'startExport' => 'startExport',
    ];

    public function mount(string $configKey, array $exportParams = [])
    {
        $this->configKey = $configKey;
        if (!empty($exportParams) && !$this->exportStarted) {
            $this->startExport($exportParams);
            $this->exportStarted = true;
        }
    }

    public function startExport(array $params)
    {
        $this->exportId = null;
        $this->status = '';
        $this->error = null;
        $this->downloadUrl = null;

        // Just dispatch to JS, no alert
        $this->dispatch('queueExport', $params);
    }

    public function render()
    {
        return view('qf::livewire.modals.export-progress');
    }
}