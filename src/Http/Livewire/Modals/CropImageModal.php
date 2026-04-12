<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Livewire\Component;

class CropImageModal extends Component
{
    public bool $showModal = false;
    public string $modalId = 'crop-image-modal';
    public ?string $imageUrl = null;
    public ?string $fieldName = null;
    public ?string $aspectRatio = '1/1';  // configurable

    protected $listeners = [
        'openCropModal' => 'openModal',
        'closeModal' => 'closeModal',
    ];

public function openModal(array $payload): void
{
    $this->imageUrl = $payload['imageUrl'];
    $this->fieldName = $payload['fieldName'];
    $this->aspectRatio = $payload['aspectRatio'] ?? '1/1';
    $this->showModal = true;
    
    // No need for dispatchBrowserEvent now; just open the modal
    $this->dispatch('open-bs-modal', ['modalId' => $this->modalId]);
}

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['imageUrl', 'fieldName', 'aspectRatio']);
        $this->dispatch('close-bs-modal', ['modalId' => $this->modalId]);
    }

public function render()
{
    return view('qf::livewire.modals.crop-image-modal', [
        'fieldName' => $this->fieldName,
        'aspectRatio' => $this->aspectRatio,
        'imageUrl' => $this->imageUrl,
        'modalId' => $this->modalId,
        'showModal' => $this->showModal,
    ]);
}
}