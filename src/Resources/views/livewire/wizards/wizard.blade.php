<div class="wizard-page-wrapper d-flex justify-content-center py-5" style="min-height: 100vh; background-color: #f8f9fa;">
    <div class="wizard-container w-100" style="max-width: 750px; margin: 0 auto; padding: 0 20px;">

        @if ($showCompletion)
            {{-- Completion screen --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success fa-4x"></i>
                    </div>
                    <h3 class="fw-bold">{{ $completion['title'] ?? 'Completed!' }}</h3>
                    <p class="text-muted fs-5">{{ $completion['message'] ?? '' }}</p>

                    <div class="mt-4">
                        @foreach ($completion['actions'] ?? [] as $action)
                            @if (isset($action['event']))
                                {{-- Button that dispatches Livewire event --}}
                                <button type="button"
                                    wire:click="dispatchCompletionEvent('{{ $action['event'] }}', {{ json_encode($action['eventParams']) }})"
                                    class="btn btn-lg {{ isset($action['primary']) && $action['primary'] ? 'btn-primary px-5' : 'btn-outline-secondary px-4' }} me-2">
                                    {{ $action['label'] }}
                                </button>
                            @elseif(isset($action['url']))
                                {{-- Standard link --}}
                                <a href="{{ str_replace('{id}', $primaryModelId, $action['url'] ?? '#') }}"
                                    class="btn btn-lg {{ isset($action['primary']) && $action['primary'] ? 'btn-primary px-5' : 'btn-outline-secondary px-4' }} me-2">
                                    {{ $action['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>


                </div>
            </div>
        @else
            {{-- Global Wizard Context --}}
            <div class="text-center mb-5">
                <h5 class="text-primary fw-bold mb-1" style="letter-spacing: 0.5px;">{{ $title }}</h5>
                <p class="text-muted small mb-0">{{ $description }}</p>
            </div>

            {{-- Wizard Progress & Step Title --}}
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <div>
                        <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem;">
                            Step {{ $currentStep + 1 }} of {{ count($steps) }}
                        </span>
                        <h2 class="fw-bold mb-0">{{ $steps[$currentStep]['title'] ?? 'Step' }}</h2>
                    </div>
                    <div class="text-muted small">
                        {{ round((($currentStep + 1) / count($steps)) * 100) }}% Complete
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="progress" style="height: 8px; background-color: #e9ecef; border-radius: 10px;">
                    <div class="progress-bar bg-primary shadow-none" role="progressbar"
                        style="width: {{ (($currentStep + 1) / count($steps)) * 100 }}%; border-radius: 10px; transition: width 0.4s ease;">
                    </div>
                </div>
            </div>

            {{-- Step content --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 p-md-5">
                    @if ($isReviewStep)
                        @include('qf::livewire.wizards.partials.wizard-review', [
                            'stepData' => $stepData,
                            'steps' => $steps,
                            'configKey' => $configKey,
                        ])
                    @else
                        @php
                            $step = $steps[$currentStep];
                            $modelConfigKey = $this->getModelConfigKey($step['model']);
                            $presetData = $this->getPresetDataForCurrentStep();
                            $stepGroups = $step['groups'] ?? [];
                            $recordId = $stepData[$currentStep] ?? null;
                        @endphp
                        <livewire:qf.wizard-form :configKey="$modelConfigKey" :presetData="$presetData" :stepIndex="$currentStep" :stepGroups="$stepGroups"
                            :recordId="$recordId" :wire:key="'step-form-'.$currentStep" />
                    @endif
                </div>
            </div>

            {{-- Navigation buttons --}}
            <div class="d-flex justify-content-between align-items-center mt-4">
                <button type="button" class="btn btn-link text-decoration-none text-muted fw-bold p-0"
                    @if ($currentStep > 0) wire:click="previous" @else disabled style="opacity:0" @endif>
                    <i class="fas fa-chevron-left me-1"></i> Back
                </button>

                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-link text-decoration-none text-danger me-4 fw-bold p-0"
                        wire:click="confirmCancel">
                        Cancel
                    </button>

                    @if ($isReviewStep)
                        <button type="button" class="btn btn-success btn-lg px-5 shadow-sm fw-bold"
                            wire:click="finish">
                            Complete Setup
                        </button>
                    @else
                        <button type="button" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold" wire:click="next">
                            Save & Continue <i class="fas fa-chevron-right ms-2"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
