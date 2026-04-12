<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Modals;

use Livewire\Component;

class AlertModal extends Component
{
    public bool $showModal = false;
    public string $modalId = 'alert-modal';

    // Core properties
    public string $type = 'confirm';
    public string $title = '';
    public string $message = '';

    // Icons
    public ?string $icon = null;                // FontAwesome class, e.g., 'fas fa-info-circle'

    // Buttons
    public string $confirmText = 'OK';
    public string $cancelText = 'Cancel';

    // Events
    public ?string $confirmEvent = null;
    public array $confirmParams = [];
    public ?string $cancelEvent = null;
    public array $cancelParams = [];

    // Prompt fields
    public ?string $inputLabel = '';
    public ?string $inputPlaceholder = '';
    public string $inputValue = '';
    public ?string $inputType = 'text';

    // Prompt validation
    public ?array $validationRules = null;      // e.g., ['required', 'min:3']
    public ?string $validationError = null;     // displayed in view

    // Auto‑close (for info/error/warning)
    public bool $autoClose = false;
    public int $autoCloseDelay = 3;             // seconds

    // Modal size: 'sm', 'lg', 'xl' (null = default)
    public ?string $size = null;

    // Dynamic rules for Livewire validation
    protected function rules()
    {
        // Only apply validation rules for prompt type
        if ($this->type === 'prompt' && $this->validationRules) {
            return ['inputValue' => $this->validationRules];
        }
        return [];
    }

    protected $listeners = [
        'showAlert' => 'show',
        'closeModal' => 'closeModal'
    ];

    public function show(array $params = []): void
    {
        // Basic properties
        $this->type            = $params['type']            ?? 'confirm';
        $this->title           = $params['title']           ?? $this->getDefaultTitle();
        $this->message         = $params['message']         ?? '';
        $this->icon            = $params['icon']            ?? $this->getDefaultIcon();

        // Button texts
        $this->confirmText     = $params['confirmText']     ?? $this->getDefaultConfirmText();
        $this->cancelText      = $params['cancelText']      ?? $this->getDefaultCancelText();

        // Events
        $this->confirmEvent    = $params['confirmEvent']    ?? null;
        $this->confirmParams   = $params['confirmParams']   ?? [];
        $this->cancelEvent     = $params['cancelEvent']     ?? null;
        $this->cancelParams    = $params['cancelParams']    ?? [];

        // Prompt specific
        if ($this->type === 'prompt') {
            $this->inputLabel       = $params['inputLabel']       ?? 'Value';
            $this->inputPlaceholder = $params['inputPlaceholder'] ?? '';
            $this->inputValue       = $params['inputValue']       ?? '';
            $this->inputType        = $params['inputType']        ?? 'text';
            $this->validationRules  = $params['validationRules']  ?? null;
            $this->validationError  = null; // reset
        }

        // Auto‑close
        $this->autoClose       = $params['autoClose']       ?? false;
        $this->autoCloseDelay  = $params['autoCloseDelay']  ?? 3;

        // Size
        $this->size            = $params['size']            ?? null;

        $this->showModal = true;
        $this->dispatch('open-bs-modal', [
            "modalId" => $this->modalId,
            "autoClose" => $this->autoClose,
            "autoCloseDelay" => $this->autoCloseDelay,
        ]);
    }

    /**
     * Real‑time validation for prompt input.
     */
    public function updatedInputValue()
    {
        if ($this->type === 'prompt' && $this->validationRules) {
            $this->validateOnly('inputValue');
            $this->validationError = null; // clear any previous error
        }
    }

public function confirm(): void
{
    // Validate if prompt with rules
    if ($this->type === 'prompt' && $this->validationRules) {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->validationError = $e->validator->errors()->first('inputValue');
            return; // stay open
        }
    }

    // Capture the input value before resetting/close
    $inputValue = $this->inputValue;

    $this->closeModal();

    // Include input value in confirmParams for prompt
    if ($this->type === 'prompt') {
        $this->confirmParams['inputValue'] = $inputValue;
    }

    if ($this->confirmEvent) {
        $this->dispatch($this->confirmEvent, $this->confirmParams);
    }
}

    public function cancel(): void
    {
        $this->closeModal();

        if ($this->cancelEvent) {
            $this->dispatch($this->cancelEvent, $this->cancelParams);
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['inputValue', 'validationError']);
        $this->dispatch('close-bs-modal', ["modalId" => $this->modalId]);
    }

    // --- Default helpers ---

    protected function getDefaultTitle(): string
    {
        return match($this->type) {
            'confirm' => 'Confirm',
            'info'    => 'Information',
            'error'   => 'Error',
            'warning' => 'Warning',
            'prompt'  => 'Input Required',
            default   => 'Alert',
        };
    }

    protected function getDefaultIcon(): string
    {
        return match($this->type) {
            'confirm' => 'fas fa-question-circle',
            'info'    => 'fas fa-info-circle',
            'error'   => 'fas fa-exclamation-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'prompt'  => 'fas fa-pencil-alt',
            default   => 'fas fa-bell',
        };
    }

    protected function getDefaultConfirmText(): string
    {
        return match($this->type) {
            'confirm' => 'Confirm',
            'info', 'error', 'warning' => 'OK',
            'prompt'  => 'Submit',
            default   => 'OK',
        };
    }

    protected function getDefaultCancelText(): string
    {
        return match($this->type) {
            'confirm', 'prompt' => 'Cancel',
            default => '',
        };
    }

    public function render()
    {
        return view('qf::livewire.modals.alert-modal');
    }
}