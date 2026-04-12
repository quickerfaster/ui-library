<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Livewire\Component;

class DocumentPreviewModal extends Component
{
    public bool $showModal = false;
    public ?string $fileUrl = null;
    public ?string $fileName = null;
    public string $modalId = 'document-preview-modal';

    protected $listeners = [
        'openDocumentPreview' => 'openModal',
        'closeModal' => 'closeModal',
    ];

    public function openModal(array $payload): void
    {
        
        $this->fileUrl = $payload['fileUrl'];
        $this->fileName = $payload['fileName'] ?? basename($this->fileUrl);
        $this->showModal = true;
        $this->dispatch('open-bs-modal', ['modalId' => $this->modalId]);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->fileUrl = null;
        $this->fileName = null;
        $this->dispatch('close-bs-modal', ['modalId' => $this->modalId]);
    }

    public function render()
    {
        return view('qf::livewire.modals.document-preview-modal');
    }
}