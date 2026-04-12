<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Livewire\Component;

class ImportModal extends Component
{
    public bool $showModal = false;
    public ?string $configKey = null;
    public string $modalId = 'import-modal';

    protected $listeners = [
        'openImportModal' => 'openModal',
        'closeModal'      => 'closeModal',
    ];

    public function openModal(string $configKey): void
    {
        $this->configKey = $configKey;
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
        return view('qf::livewire.modals.import-modal');
    }
}