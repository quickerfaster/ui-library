<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;

class PolicyCalculationBuilder extends Component
{
    public string $policyType = 'benefit';
    public ?string $existingJson = null;  // only used for tax policies now

    // For tax
    public array $bands = [];

    // For non‑tax (new structure)
    public string $calculationType = 'percentage';
    public float $employeeValue = 0;
    public float $employerValue = 0;

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
        $this->updatedBands();
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

    public function updatedBands(): void
    {
        $this->updateParent();
    }

    // ---------- Non‑Tax Methods ----------
    public function updatedCalculationType(): void
    {
        $this->updateParent();
    }

    public function updatedEmployeeValue(): void
    {
        $this->employeeValue = $this->employeeValue ?? 0;
        $this->updateParent();
    }

    public function updatedEmployerValue(): void
    {
        $this->employerValue = $this->employerValue ?? 0;
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
            // Load from new structure
            $this->calculationType = $data['calculation_type'] ?? 'percentage';
            $this->employeeValue = $data['employee_value'] ?? 0;
            $this->employerValue = $data['employer_value'] ?? 0;
        }
    }

    protected function resetToDefault(): void
    {
        if ($this->policyType === 'tax') {
            $this->bands = [['limit' => '', 'rate' => '']];
        } else {
            $this->calculationType = 'percentage';
            $this->employeeValue = 0;
            $this->employerValue = 0;
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
            // New structure
            return json_encode([
                'calculation_type' => $this->calculationType,
                'employee_value' => (float) $this->employeeValue,
                'employer_value' => (float) $this->employerValue,
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