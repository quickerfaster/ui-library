<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Livewire\Component;

class ExportModal extends Component
{
    public bool $showModal = false;
    public ?string $configKey = null;
    public array $exportParams = [];
    public string $modalId = 'export-modal';

    // Export state
    public ?int $exportId = null;
    public string $status = '';
    public ?string $error = null;
    public ?string $downloadUrl = null;
    public ?int $fileSize = null;
    public int $completedChunks = 0;
    public int $totalChunks = 0;

    protected $listeners = [
        'openExportModal' => 'openModal',
        'closeExportModal' => 'closeModal',
    ];

    public function openModal(array $payload): void
    {
        // Reset previous export state
        $this->resetExportState();

        $this->configKey = $payload['configKey'];
        $this->exportParams = $payload['params'] ?? [];
        $this->showModal = true;

        // Start the export (JS will listen for this event)
        $this->dispatch('startExport', $this->exportParams);

        $this->dispatch('open-bs-modal', ["modalId" => $this->modalId]);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('close-bs-modal', ["modalId" => $this->modalId]);
    }

    public function cancelExport(): void
    {
        if ($this->exportId) {
            $this->dispatch('cancelExport', $this->exportId);
        }
    }



    public function resetExportState(): void
    {
        $this->exportId = null;
        $this->status = '';
        $this->error = null;
        $this->downloadUrl = null;
        $this->fileSize = null;
        $this->completedChunks = 0;
        $this->totalChunks = 0;
    }

    public function render()
    {
        return view('qf::livewire.modals.export-modal');
    }
}