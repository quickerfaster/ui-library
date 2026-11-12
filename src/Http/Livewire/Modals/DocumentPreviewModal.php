<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Illuminate\Support\Str;
use Livewire\Component;

class DocumentPreviewModal extends Component
{
    public bool $showModal = false;
    public ?string $fileUrl = null;
    public ?string $fileName = null;
    public ?string $mimeType = null;
    public string $previewNonce = '';
    public string $modalId = 'document-preview-modal';

    protected $listeners = [
        'openDocumentPreview' => 'openModal',
        'closeModal' => 'closeModal',
        'documentPreviewModalClosed' => 'documentPreviewModalClosed',
    ];

    public function openModal(array $payload): void
    {
        $this->fileUrl = $payload['fileUrl'];
        $this->fileName = $payload['fileName'] ?? basename($this->fileUrl);
        $this->mimeType = $payload['mimeType'] ?? null;
        $this->previewNonce = Str::random(16);
        $this->showModal = true;
        $this->dispatch('open-bs-modal', ['modalId' => $this->modalId]);
    }

    public function closeModal(): void
    {
        $this->resetState();
        $this->dispatch('close-bs-modal', ['modalId' => $this->modalId]);
    }

    public function documentPreviewModalClosed(): void
    {
        $this->resetState();
    }

    protected function resetState(): void
    {
        $this->showModal = false;
        $this->fileUrl = null;
        $this->fileName = null;
        $this->mimeType = null;
        $this->previewNonce = '';
    }

    public function render()
    {
        return view('qf::livewire.modals.document-preview-modal');
    }
}