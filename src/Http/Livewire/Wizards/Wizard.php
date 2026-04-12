<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Wizards;

use Livewire\Component;
use Illuminate\Support\Str;
use QuickerFaster\UILibrary\Services\Config\Wizards\WizardConfigResolver;

class Wizard extends Component
{
    public string $configKey;           // e.g. "hr.employee_onboarding"
    public string $wizardId;             // unique per user/session
    public array $steps = [];
    public array $models = [];
    public int $primaryModelId;
    public array $completion = [];
    public int $currentStep = 0;
    public array $stepData = [];         // stores record IDs per step index
    public array $createdRecordIds = []; // track all IDs created in this session
    public array $createdRecords = []; // each entry: ['model' => ModelClass, 'id' => id]

    public string $title = '';
    public string $description = '';
    public string $returnPath = '';
    

    protected $listeners = [
        'stepFormSaved' => 'handleStepFormSaved',
        'cancelKeep' => 'cancelKeep',
        'cancelDelete' => 'cancelDelete',
    ];

    public function mount(string $configKey): void
    {
        
        $this->configKey = $configKey;
        $this->wizardId = 'wizard-' . md5($configKey . '-' . auth()->id());

        $resolver = new WizardConfigResolver($configKey);
        $this->steps = $resolver->getSteps();
        $this->models = $resolver->getModels();
        $this->completion = $resolver->getCompletion();
        $this->title = $resolver->getTitle();
        $this->description = $resolver->getDescription();
        $this->returnPath = $resolver->getReturnPath();

        // Restore from session if exists
        if (session()->has($this->wizardId)) {
            $data = session()->get($this->wizardId);
            $this->stepData = $data['stepData'] ?? [];
            $this->currentStep = $data['currentStep'] ?? 0;
            $this->createdRecordIds = $data['createdRecordIds'] ?? [];
        }
    }

    public function goToStep(int $index): void
    {
        if ($index >= 0 && $index < count($this->steps)) {
            $this->currentStep = $index;
            $this->saveToSession();
        }
    }

    public function next(): void
    {
        $step = $this->steps[$this->currentStep] ?? null;
        if (!$step)
            return;

        if ($this->isFormStep($step)) {
            // Dispatch a single event with the step index
            $this->dispatch('saveStepForm', stepIndex: $this->currentStep);
        } else {
            $this->advance();
        }
    }

    public function previous(): void
    {
        if ($this->currentStep > 0) {
            $this->currentStep--;
            $this->saveToSession();
        }
    }

    public function cancel(): void
    {
        session()->forget($this->wizardId);
        // Redirect to a sensible default – you can make this configurable
        redirect()->to('/');
    }

    public function finish(): void
    {
        session()->forget($this->wizardId);
        $this->currentStep = count($this->steps); // special index for completion
    }

    public function handleStepFormSaved(int $recordId, int $stepIndex): void
    {
        $step = $this->steps[$stepIndex];
        $modelClass = $step['model'];
        $this->stepData[$stepIndex] = $recordId;
        $this->createdRecords[] = ['model' => $modelClass, 'id' => $recordId];
        if ($modelClass === $this->models["primary"]) // Keep the ref to the primaryModelId
            $this->primaryModelId = $recordId;

        $this->saveToSession();
        $this->advance();
    }



    public function getPrimaryModelData(): array
    {
        foreach ($this->createdRecords as $createdRecord) {
            if ($createdRecord["model"] === $this->models["primary"]) {
                // Return immediately once the primary model is found
                return [
                    "class" => $createdRecord["model"], 
                    "id"    => $createdRecord["id"] // Assuming 'id' is the key for the record ID
                ];
            }
        }

        return []; // Return empty if not found
    }







// In your Wizard component, enhance dispatchCompletionEvent to support multiple placeholders
public function dispatchCompletionEvent(string $eventName, array $params): void
{
    // Replace placeholders like {employee_id}, {position_id} with actual record IDs
    $placeholders = [
        '{id}' => $this->primaryModelId,
        // '{employee_id}' => $this->stepData[0] ?? null,   // assuming step 0 is Employee
        // '{position_id}' => $this->stepData[1] ?? null,   // assuming step 1 is EmployeePosition
    ];
    
    array_walk_recursive($params, function (&$value) use ($placeholders) {
        if (is_string($value)) {
            $value = str_replace(array_keys($placeholders), array_values($placeholders), $value);
        }
    });
    
    $this->dispatch($eventName, ...array_values($params));
}









    /**
     * Show the cancellation confirmation dialog.
     */
    public function confirmCancel(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Cancel Wizard?',
            'message' => 'Any data you have already saved will remain in the system. Do you want to keep it or delete all progress?',
            'icon' => 'fas fa-question-circle',
            'confirmText' => 'Keep Data',
            'cancelText' => 'Delete Progress',
            'confirmEvent' => 'cancelKeep',
            'cancelEvent' => 'cancelDelete',
        ]);

    }

    public function cancelKeep(): void
    {
        session()->forget($this->wizardId);
        $this->redirect($this->returnPath?? '/');
    }

    public function cancelDelete(): void
    {
        \DB::transaction(function () {
            foreach ($this->createdRecords as $record) {
                $modelClass = $record['model'];
                $modelClass::destroy($record['id']);
            }
        });
        session()->forget($this->wizardId);
        $this->redirect($this->returnPath?? '/');
    }





    protected function advance(): void
    {
        if ($this->currentStep + 1 < count($this->steps)) {
            $this->currentStep++;
        } else {
            $this->finish();
        }
        $this->saveToSession();
    }

    protected function isFormStep(array $step): bool
    {
        return isset($step['model']) && !isset($step['isReview']); // review step has no model
    }

    protected function getChildComponentId(): string
    {
        return 'step-form-' . $this->currentStep;
    }

    /**
     * Build preset data for the current step (e.g., foreign keys from previous steps)
     */
    protected function getPresetDataForCurrentStep(): array
    {
        $step = $this->steps[$this->currentStep] ?? [];
        $preset = [];

        if (isset($step['requiresLink']) && $step['requiresLink']) {
            // Find the source step (isLinkSource = true)
            foreach ($this->steps as $index => $s) {
                if (isset($s['isLinkSource']) && $s['isLinkSource']) {
                    $sourceRecordId = $this->stepData[$index] ?? null;
                    if ($sourceRecordId) {
                        $linkFields = (new WizardConfigResolver($this->configKey))->getLinkFields();
                        $foreignKey = $linkFields['databaseField'] ?? 'employee_id';
                        $preset[$foreignKey] = $sourceRecordId;
                    }
                    break;
                }
            }
        }

        return $preset;
    }

    /**
     * Convert a model class to a config key that ConfigResolver understands.
     * Assumes models are in App\Modules\{Module}\Models and config keys are like "hr_employee".
     */
    protected function getModelConfigKey(string $modelClass): string
    {
        $parts = explode('\\', $modelClass);
        // Find the module name (after 'Modules')
        $moduleIndex = array_search('Modules', $parts) + 1;
        $module = strtolower($parts[$moduleIndex] ?? '');
        $modelName = Str::snake(class_basename($modelClass));
        return $module . '_' . $modelName;
    }

    protected function saveToSession(): void
    {
        session()->put($this->wizardId, [
            'stepData' => $this->stepData,
            'currentStep' => $this->currentStep,
            'createdRecordIds' => $this->createdRecordIds,
        ]);
    }

    public function render()
    {
        $currentStepConfig = $this->steps[$this->currentStep] ?? null;
        $isReviewStep = $currentStepConfig && !isset($currentStepConfig['model']); // no model = review

        return view('qf::livewire.wizards.wizard', [
            'currentStepConfig' => $currentStepConfig,
            'isReviewStep' => $isReviewStep,
            'showCompletion' => $this->currentStep === count($this->steps),
        ]);
    }
}