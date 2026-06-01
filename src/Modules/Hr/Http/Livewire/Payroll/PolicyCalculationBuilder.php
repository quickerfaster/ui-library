<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;

class PolicyCalculationBuilder extends Component
{
    public string $policyType = 'benefit';
    public ?string $existingJson = null;

    public array $bands = [];
    public string $calcType = 'percentage';
    public float $calcValue = 0;

    protected $listeners = [
        'parentPolicyTypeChanged' => 'setPolicyType',
    ];

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

    // ---------- Tax Bands Methods ----------
    public function addBand(): void
    {
        $this->bands[] = ['limit' => '', 'rate' => ''];
        $this->updatedBands(); // trigger save
    }

    public function removeBand(int $index): void
    {
        unset($this->bands[$index]);
        $this->bands = array_values($this->bands);
        if (empty($this->bands)) {
            $this->bands = [['limit' => '', 'rate' => '']];
        }
        $this->updatedBands();
    }

    // Called automatically when $bands changes (via wire:model.live)
    public function updatedBands(): void
    {
        $this->updateParent();
    }

    public function updatedCalcValue(): void
    {
        $this->calcValue = $this->calcValue ?? 0.0; // To avoid empty null value error

        $this->updateParent();
    }

    public function updatedCalcType(): void
    {
        $this->updateParent();
    }

    // ---------- JSON Handling ----------
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
            $rawBands = $data['bands'] ?? [];
            if (empty($rawBands)) {
                $this->bands = [['limit' => '', 'rate' => '']];
            } else {
                $this->bands = array_map(function ($band) {
                    if (is_array($band) && !isset($band['limit']) && count($band) >= 2) {
                        return ['limit' => $band[0], 'rate' => $band[1]];
                    }
                    return [
                        'limit' => $band['limit'] ?? '',
                        'rate' => $band['rate'] ?? '',
                    ];
                }, $rawBands);
            }
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

    protected function buildJson(): string
    {
        if ($this->policyType === 'tax') {
            $cleanBands = [];
            foreach ($this->bands as $band) {
                $limit = isset($band['limit']) && $band['limit'] !== '' ? (float) $band['limit'] : null;
                $rate = isset($band['rate']) && $band['rate'] !== '' ? (float) $band['rate'] : null;

                // Skip row if both limit and rate are empty/zero
                if (($limit === null || $limit == 0) && ($rate === null || $rate == 0)) {
                    continue;
                }

                $cleanBands[] = [(float) $limit, (float) $rate];
            }

            if (empty($cleanBands)) {
                $cleanBands = [[0, 0]];
            }
            return json_encode(['bands' => $cleanBands]);
        } else {
            return json_encode([
                'type' => $this->calcType,
                'value' => floatval($this->calcValue),
            ]);
        }
    }

    protected function updateParent(): void
    {
        $json = $this->buildJson();
        $this->dispatch('calculationLogicUpdated', $json);
    }

    public function render()
    {
        return view('hr::livewire.payroll.policy-calculation-builder');
    }
}
