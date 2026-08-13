<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Approvals;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\ApprovalRequest;

class ApprovalHistoryTimeline extends Component
{
    public string $configKey;
    public int $approvableId;
    public ?ApprovalRequest $request = null;

    protected $listeners = ['refreshApprovalTimeline' => '$refresh'];

    public function mount(string $configKey, int $approvableId): void
    {
        $this->configKey = $configKey;
        $this->approvableId = $approvableId;
        $this->loadRequest();
    }

    protected function loadRequest(): void
    {
        $resolver = app(\QuickerFaster\UILibrary\Services\Config\Approvals\ApprovalConfigResolver::class, ['configKey' => $this->configKey]);
        $modelClass = $resolver->getModelClass();
        $approvable = $modelClass::find($this->approvableId);
        $this->request = $approvable?->approvalRequest;
    }

    public function render()
    {
        $tiers = $this->request ? $this->request->tiers()->orderBy('sequence')->get() : collect();
        return view('qf::livewire.approvals.timeline', [
            'tiers' => $tiers,
            'request' => $this->request,
        ]);
    }
}