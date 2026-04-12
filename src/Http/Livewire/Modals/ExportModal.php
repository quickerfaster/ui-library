<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Livewire\Component;

class ExportModal extends Component
{
    public bool $showModal = false;
    public ?string $configKey = null;
    public array $exportParams = [];
    public string $modalId = 'export-modal';

    protected $listeners = [
        'openExportModal' => 'openModal',
        'closeExportModal' => 'closeModal',
    ];

    public function openModal(array $payload): void
    {
        $this->configKey = $payload['configKey'];
        $this->exportParams = $payload['params'] ?? [];
        $this->showModal = true;
        $this->dispatch('open-bs-modal', ["modalId" => $this->modalId]);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('close-bs-modal', ["modalId" => $this->modalId]);
    }

    public function render()
    {
        return view('qf::livewire.modals.export-modal');
    }
}