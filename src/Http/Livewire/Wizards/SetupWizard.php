<?php 
namespace QuickerFaster\UILibrary\Http\Livewire\Wizards;

use QuickerFaster\UILibrary\Http\Livewire\Wizards\Wizard;
use QuickerFaster\UILibrary\Models\SystemSetting;

class SetupWizard extends Wizard
{
    public function mount(string $configKey = null): void
    {
        // Do NOT call parent::mount() – we handle initialization ourselves
        $this->configKey = $configKey ?? 'setup'; // placeholder, not used
        $this->wizardId = 'setup-wizard-' . auth()->id();

        $config = config('app_setup');

        $this->steps = $config['steps'];
        $this->completion = $config['completion'];

        // Restore from session if exists
        if (session()->has($this->wizardId)) {
            $data = session()->get($this->wizardId);
            $this->stepData = $data['stepData'] ?? [];
            $this->currentStep = $data['currentStep'] ?? 0;
            $this->createdRecordIds = $data['createdRecordIds'] ?? [];
        }
    }

    /**
     * Build preset data for the current step (e.g., foreign keys from previous steps).
     */
    protected function getPresetDataForCurrentStep(): array
    {
        $stepIndex = $this->currentStep;
        $preset = [];
        $linkMap = config('setup.link_map', []);

        foreach ($linkMap as $sourceIndex => $foreignKeyField) {
            if (isset($this->stepData[$sourceIndex]) && $sourceIndex < $stepIndex) {
                $preset[$foreignKeyField] = $this->stepData[$sourceIndex];
            }
        }

        return $preset;
    }

    /**
     * After finishing the wizard, mark setup as complete.
     */
    public function finish(): void
    {
        parent::finish(); // clears session, sets currentStep to count

        $setting = SystemSetting::first();
        if ($setting) {
            $setting->setup_completed = true;
            $setting->save();
        }

        session()->flash('setup_completed', true);
    }

    /**
     * Render the wizard view.
     */
    public function render()
    {
        return view('qf::livewire.wizards.wizard', [
            'currentStepConfig' => $this->steps[$this->currentStep] ?? null,
            'isReviewStep' => !isset($this->steps[$this->currentStep]['model']),
            'showCompletion' => $this->currentStep === count($this->steps),
        ]);
    }
}