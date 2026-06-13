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
    public ?int $fileSize = null;

    public int $completedChunks = 0;
    public int $totalChunks = 0;

    // You can also add a flag to track if modal was closed (optional for auto‑reopen)
    // public bool $modalWasClosed = false;

    protected $listeners = [
        'startExport' => 'startExport',
    ];

    public ?int $resumeExportId = null;

    public function mount(string $configKey, array $exportParams = [], ?int $resumeExportId = null)
    {
        $this->configKey = $configKey;
        $this->resumeExportId = $resumeExportId;

        if ($resumeExportId) {
            $this->exportId = $resumeExportId;
            $this->status = 'processing';
            $this->exportStarted = true;
            $this->dispatch('startPollingForExport', $resumeExportId);
        } elseif (!empty($exportParams) && !$this->exportStarted) {
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


    public function cancelExport()
    {
        if ($this->exportId) {
            $this->dispatch('cancelExport', exportId: $this->exportId);
        }
    }

    public function render()
    {
        return view('qf::livewire.modals.export-progress');
    }
}