<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;

class DetailModal extends Component
{
    public bool $showModal = false;
    public ?string $configKey = null;
    public ?int $recordId = null;
    public string $modalId = 'detail-modal';
    public ?string $customComponent = null; // new

    public ?array $recordIds = null;
public ?int $currentIndex = null;

    protected $listeners = [
        'openDetailModal' => 'openDetailModal',
        'closeModal'      => 'closeModal',
            'editFromDetail' => 'editFromDetail', // new

    ];


public function openDetailModal(string $configKey, int $recordId, ?array $recordIds = null, ?int $currentIndex = null): void
{
    $this->configKey = $configKey;
    $this->recordId  = $recordId;
    $this->recordIds = $recordIds;
    $this->currentIndex = $currentIndex;

    // Load config to check for custom component
    $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
    $this->customComponent = $resolver->getConfig()['detailComponent'] ?? null;

    $this->showModal = true;
    $this->dispatch('open-bs-modal', ["modalId" => $this->modalId]);
}

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('close-bs-modal', ["modalId" => $this->modalId]);
    }



public function editFromDetail($configKey, $recordId): void
{
    $this->closeModal(); // close the detail modal
    $this->dispatch('openEditModal', $configKey, $recordId);
}



    public function render()
    {
        return view('qf::livewire.modals.detail-modal', [
            'customComponent' => $this->customComponent,
            'configKey'       => $this->configKey,
            'recordId'        => $this->recordId,
                        'recordIds' => $this->recordIds,
            'currentIndex' => $this->currentIndex,
        ]);
    }
}