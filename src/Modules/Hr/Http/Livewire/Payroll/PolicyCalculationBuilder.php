<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;

class PolicyCalculationBuilder extends Component
{
    public string $policyType = 'benefit';   // default
    public ?string $existingJson = null;

    // For tax
    public array $bands = [];

    // For others
    public string $calcType = 'percentage';
    public float $calcValue = 0;

    protected $listeners = ['parentPolicyTypeChanged' => 'setPolicyType'];

    public function mount(string $policyType, ?string $existingJson = null)
    {
        $this->policyType = $policyType;
        $this->existingJson = $existingJson;
        $this->loadFromJson();
    }

    public function setPolicyType(string $newType): void
    {
        $this->policyType = $newType;
        $this->resetToDefault();
    }

    protected function loadFromJson(): void
    {
        if (empty($this->existingJson)) {
            $this->resetToDefault();
            return;
        }

        $data = json_decode($this->existingJson, true);
        if (!is_array($data)) {
            $this->resetToDefault();
            return;
        }

        if ($this->policyType === 'tax') {
            $this->bands = $data['bands'] ?? [['limit' => '', 'rate' => '']];
        } else {
            $this->calcType = $data['type'] ?? 'percentage';
            $this->calcValue = $data['value'] ?? 0;
        }
    }

    protected function resetToDefault(): void
    {
        if ($this->policyType === 'tax') {
            $this->bands = [['limit' => '', 'rate' => '']];
        } else {
            $this->calcType = 'percentage';
            $this->calcValue = 0;
        }
        $this->updateParent();
    }

    public function updated($property): void
    {
        $this->updateParent();
    }

    public function updatedBands(): void
    {
        $this->updateParent();
    }

    protected function updateParent(): void
    {
        $json = $this->buildJson();
        $this->dispatch('calculationLogicUpdated', $json);
    }

    protected function buildJson(): string
    {
        if ($this->policyType === 'tax') {
            $bands = [];
            foreach ($this->bands as $band) {
                $limit = floatval($band['limit']);
                $rate = floatval($band['rate']);
                // Skip empty rows (limit and rate both zero)
                if ($limit == 0 && $rate == 0 && count($this->bands) > 1) {
                    continue;
                }
                $bands[] = [$limit, $rate];
            }
            if (empty($bands)) {
                $bands = [[0, 0]];
            }
            return json_encode(['bands' => $bands]);
        } else {
            return json_encode([
                'type' => $this->calcType,
                'value' => floatval($this->calcValue),
            ]);
        }
    }

    public function render()
    {
        return view('hr::livewire.payroll.policy-calculation-builder');
    }
}