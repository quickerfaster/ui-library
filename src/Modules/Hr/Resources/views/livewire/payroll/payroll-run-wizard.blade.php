<div wire:key="payroll-run-wizard">
    <div class="wizard-page-wrapper d-flex justify-content-center py-5"
        style="min-height: 100vh; background-color: #f8f9fa;">
        <div class="wizard-container w-100" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">

            {{-- Progress bar --}}
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <div>
                        <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem;">
                            Step {{ $currentStep }} of 3
                        </span>
                        <h2 class="fw-bold mb-0">
                            @switch($currentStep)
                                @case(1)
                                    Verification
                                @break

                                @case(2)
                                    Adjustments
                                @break

                                @case(3)
                                    Review & Preview
                                @break
                            @endswitch
                        </h2>
                    </div>
                    <div class="text-muted small">
                        {{ round(($currentStep / 3) * 100) }}% Complete
                    </div>
                </div>

                <div class="progress" style="height: 8px; background-color: #e9ecef; border-radius: 10px;">
                    <div class="progress-bar bg-primary shadow-none"
                        style="width: {{ ($currentStep / 3) * 100 }}%; border-radius: 10px;">
                    </div>
                </div>
            </div>

            {{-- Step content --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 p-md-5">

                    {{-- STEP 1 --}}
                    <div class="{{ $currentStep === 1 ? '' : 'd-none' }}">
                        <h4>Payroll Details</h4>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label>Pay Schedule</label>
                                <select wire:model="pay_schedule_id" class="form-control">
                                    <option value="">Select...</option>
                                    @foreach (\App\Modules\Hr\Models\PaySchedule::where('is_active', true)->get() as $schedule)
                                        <option value="{{ $schedule->id }}">
                                            {{ $schedule->name }} ({{ $schedule->frequency }})
                                        </option>
                                    @endforeach
                                </select>

                                @error('pay_schedule_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label>Period Start</label>
                                <input type="date" wire:model="period_start" class="form-control">
                                @error('period_start')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label>Period End</label>
                                <input type="date" wire:model="period_end" class="form-control">
                                @error('period_end')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- STEP 2 --}}
                    <div class="{{ $currentStep === 2 ? '' : 'd-none' }}">
                        @if ($payrollRunId)
                            <livewire:qf.payroll-wizard-adjustments :stepIndex="2" :payrollRunId="$payrollRunId"
                                wire:key="adjustments-{{ $payrollRunId }}" />
                        @endif
                    </div>

                    {{-- STEP 3 --}}
                    <div class="{{ $currentStep === 3 ? '' : 'd-none' }}">
                        @if ($payrollRunId)
                            <livewire:qf.payroll-wizard-preview :stepIndex="3" :payrollRunId="$payrollRunId"
                                wire:key="preview-{{ $payrollRunId }}" />
                        @endif
                    </div>

                </div>
            </div>

            {{-- Navigation --}}
            <div class="d-flex justify-content-between align-items-center mt-4">

                {{-- Back --}}
                <button type="button" class="btn btn-link text-decoration-none text-muted fw-bold p-0"
                    wire:click="goToStep({{ $currentStep - 1 }})" wire:loading.attr="disabled"
                    @if ($currentStep <= 1 || $isProcessing) disabled 
                        style="opacity: {{ $currentStep <= 1 ? '0' : '0.5' }}; pointer-events: none;" @endif>
                    <i class="fas fa-chevron-left me-1"></i> Back
                </button>


                <div class="d-flex align-items-center">

                    {{-- Cancel --}}
                    <button type="button" class="btn btn-link text-decoration-none text-danger me-4 fw-bold p-0"
                        wire:click="confirmCancel()" @if ($isProcessing) disabled @endif>
                        Cancel
                    </button>

                    {{-- Next --}}
                    @if ($currentStep == 1)
                        <button type="button" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold"
                            wire:click="goToStep2" @if ($isProcessing) disabled @endif>
                            Continue <i class="fas fa-chevron-right ms-2"></i>
                        </button>
                    @elseif($currentStep == 2)
                        <button type="button" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold"
                            wire:click="$dispatch('saveAdjustments')"
                            @if ($isProcessing) disabled @endif>
                            Save & Continue <i class="fas fa-chevron-right ms-2"></i>
                        </button>
                    @elseif($currentStep == 3)
                        <button type="button" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold"
                            wire:click="$dispatch('savePreview')" @if ($isProcessing) disabled @endif>
                            Complete Setup <i class="fas fa-check ms-2"></i>
                        </button>
                    @endif

                </div>
            </div>

        </div>
    </div>
</div>
