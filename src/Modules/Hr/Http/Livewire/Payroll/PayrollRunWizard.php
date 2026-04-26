<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;
use App\Modules\Hr\Models\PaySchedule;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\PayrollRunAdjustment;
use App\Modules\Hr\Models\PayrollPayslip;
use Illuminate\Support\Facades\DB;

class PayrollRunWizard extends Component
{
    public int $currentStep = 1;
    public ?int $payrollRunId = null;
    public $pay_schedule_id = null;
    public $period_start = null;
    public $period_end = null;
    public array $stepData = [];

    public bool $isProcessing = false;


    protected $listeners = [
        'adjustmentsComplete' => 'goToStep3',
        'previewComplete' => 'finalize',
        'cancelKeep' => 'cancelKeep',
        'cancelDelete' => 'cancelDelete',

        'processingStarted' => 'setProcessing',
        'processingFinished' => 'clearProcessing',
    ];

    public function mount($payrollRunId = null)
    {
        $wizardId = $this->getWizardId();
        if (session()->has($wizardId)) {
            $data = session()->get($wizardId);
            $this->currentStep = $data['currentStep'] ?? 1;
            $this->payrollRunId = $data['payrollRunId'] ?? null;
            $this->pay_schedule_id = $data['pay_schedule_id'] ?? null;
            $this->period_start = $data['period_start'] ?? null;
            $this->period_end = $data['period_end'] ?? null;
            $this->stepData = $data['stepData'] ?? [];
        } elseif ($payrollRunId) {
            $run = PayrollRun::findOrFail($payrollRunId);
            $this->payrollRunId = $run->id;
            $this->pay_schedule_id = $run->pay_schedule_id;
            $this->period_start = $run->period_start->format('Y-m-d');
            $this->period_end = $run->period_end->format('Y-m-d');
            $this->currentStep = $run->current_step ?? 1;
            $this->stepData = ['payroll_run_id' => $run->id];
            $this->saveToSession();
        } else {
            $this->saveToSession();
        }
    }

    protected function getWizardId(): string
    {
        return 'payroll-wizard-' . auth()->id();
    }

    protected function saveToSession(): void
    {
        session()->put($this->getWizardId(), [
            'currentStep' => $this->currentStep,
            'payrollRunId' => $this->payrollRunId,
            'pay_schedule_id' => $this->pay_schedule_id,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'stepData' => $this->stepData,
        ]);
    }

    public function goToStep($step)
    {
        $this->currentStep = $step;
        $this->saveToSession();
    }

    public function goToStep2()
    {
        $this->validate([
            'pay_schedule_id' => 'required|exists:pay_schedules,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);

        DB::transaction(function () {
            if (!$this->payrollRunId) {
                $run = PayrollRun::create([
                    'pay_schedule_id' => $this->pay_schedule_id,
                    'period_start' => $this->period_start,
                    'period_end' => $this->period_end,
                    'status' => 'draft',
                    'calculation_status' => 'pending', // <-- Added
                    'current_step' => 2,
                ]);
                $this->payrollRunId = $run->id;
                $this->stepData = ['payroll_run_id' => $run->id];
            } else {
                $run = PayrollRun::find($this->payrollRunId);
                $run->update([
                    'pay_schedule_id' => $this->pay_schedule_id,
                    'period_start' => $this->period_start,
                    'period_end' => $this->period_end,
                    'current_step' => 2,
                ]);
            }
        });

        $this->currentStep = 2;
        $this->saveToSession();
    }

    public function goToStep3()
    {
        $this->currentStep = 3;
        $this->saveToSession();
        $this->dispatch('refreshPreview');
    }

    /**
     * Finalize the wizard – does NOT run calculation synchronously.
     * The calculation is handled by the queue job (triggered by the preview component).
     */
    public function finalize()
    {
        $run = PayrollRun::findOrFail($this->payrollRunId);
        $run->update([
            'status' => 'ready_for_review',
            'current_step' => 4,
        ]);

        // Clear wizard session
        session()->forget($this->getWizardId());

        session()->flash('message', 'Payroll run submitted. The calculation is being processed in the background. You will be notified when ready for review.');
        return redirect()->route('payroll-runs.show', $this->payrollRunId);
    }


    public function setProcessing(): void
    {
        $this->isProcessing = true;
    }

    public function clearProcessing(): void
    {
        $this->isProcessing = false;
    }



    public function confirmCancel(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Cancel Payroll Wizard?',
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
        session()->forget($this->getWizardId());
        $this->redirect('/hr/payroll-runs');
    }

    public function cancelDelete(): void
    {
        DB::transaction(function () {
            if ($this->payrollRunId) {
                PayrollRunAdjustment::where('payroll_run_id', $this->payrollRunId)->delete();
                PayrollPayslip::where('payroll_run_id', $this->payrollRunId)->delete();
                PayrollRun::destroy($this->payrollRunId);
            }
        });
        session()->forget($this->getWizardId());
        $this->redirect('/hr/payroll-runs');
    }

    public function render()
    {
        return view('hr::livewire.payroll.payroll-run-wizard', [
            'currentStep' => $this->currentStep,
            'payrollRunId' => $this->payrollRunId,
            'paySchedule' => $this->pay_schedule_id ? PaySchedule::find($this->pay_schedule_id) : null,
            'errorBag' => $this->getErrorBag(),
        ]);
    }
}