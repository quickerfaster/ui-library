<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Livewire\Component;

class FormModal extends Component
{
    // Controls modal visibility
    public bool $showModal = false;

    // Data to pass to the form
    public ?string $configKey = null;
    public ?int $recordId = null;
    public ?string $modalId = 'form-modal'; // HTML id of the modal
    public array $allowedGroups = [];
    public array $prefilledData = [];

    protected $listeners = [
        'openAddModal' => 'openAddModal',
        'openEditModal' => 'openEditModal',
        'closeModal' => 'closeModal',
        'formSaved' => 'closeModal', // optional: close after save
    ];

    /**
     * Open modal for adding a new record.
     */


public function openAddModal(string $configKey, array $prefilledData = [], array $allowedGroups = []): void
{
    $this->configKey = $configKey;
    $this->recordId = null;
    $this->prefilledData = $prefilledData;
    $this->allowedGroups = $allowedGroups;
    $this->showModal = true;

    $this->dispatch('open-bs-modal', [
        'modalId' => $this->modalId,
        'allowedGroups' => $this->allowedGroups
    ]);

    // Instead of resetForm, dispatch the new event to refresh the form
    $this->dispatch('resetAndPrefill', prefilledData: $prefilledData);
}





    /**
     * Open modal for editing an existing record.
     */
    public function openEditModal(string $configKey, int $recordId, array $allowedGroups = []): void
    {
        $this->configKey = $configKey;
        $this->recordId = $recordId;
        $this->allowedGroups = $allowedGroups;

        $this->showModal = true;
        $this->dispatch('open-bs-modal', ['modalId' => $this->modalId, 'allowedGroups' => $this->allowedGroups]);
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        // $this->reset(['configKey', 'recordId', 'prefilledData', 'allowedGroups']);
        $this->dispatch('close-bs-modal', ['modalId' => $this->modalId]);
        // Optionally reset form data after closing
        // $this->dispatch('resetForm');
    }

    public function render()
    {
        return view('qf::livewire.modals.form-modal');
    }
}
