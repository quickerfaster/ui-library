<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Services\Payroll\PayrollCalculator;
use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Traits\HasCurrencySymbol;



class PayrollRunDetail extends Component
{
    use HasCurrencySymbol;
    
    public int $recordId;
    public string $configKey;
    public PayrollRun $run;
    public array $returnParams = [];
    public array $tabs = [];
    public string $activeTab = 'overview';

    protected $listeners = [
        'refreshDetail' => '$refresh',
        'approveRun' => 'approve',
        'markPaid' => 'markPaid',
        'cancelRun' => 'cancel',
        'recalculate' => 'recalculate',
    ];

    public function mount(int $recordId, string $configKey, array $returnParams = []): void
    {
        $this->recordId = $recordId;
        $this->configKey = $configKey;
        $this->returnParams = $returnParams;
        $this->run = PayrollRun::with(['paySchedule'])->findOrFail($recordId);
        $this->tabs = $this->getTabs();
    }

    protected function getTabs(): array
    {
        return [
            'overview' => ['title' => 'Overview', 'icon' => 'fas fa-info-circle'],
            'payslips' => ['title' => 'Payslips', 'icon' => 'fas fa-receipt'],
            'adjustments' => ['title' => 'Adjustments', 'icon' => 'fas fa-edit'],
            'audit' => ['title' => 'Audit', 'icon' => 'fas fa-history'],
        ];
    }

    // Approval actions (unchanged)
    public function confirmApprove(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Approve Payroll Run?',
            'message' => 'This will lock all data and mark the run as approved. Are you sure?',
            'confirmEvent' => 'approveRun',
            'confirmParams' => [],
        ]);
    }

    public function approve(): void
    {
        if (!in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review'])) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Cannot approve this payroll run.']);
            return;
        }

        DB::transaction(function () {
            $this->run->update([
                'status' => 'approved',
                'approved_by' => auth()->user()->name ?? auth()->id(),
                'approved_at' => now(),
            ]);
        });

        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Payroll run approved.']);
        $this->run->refresh();
    }

    public function confirmMarkPaid(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Mark as Paid?',
            'message' => 'Confirm that payment has been processed externally?',
            'confirmEvent' => 'markPaid',
            'confirmParams' => [],
        ]);
    }

    public function markPaid(): void
    {
        if ($this->run->status !== 'approved') {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Only approved runs can be marked as paid.']);
            return;
        }

        $this->run->update([
            'status' => 'paid',
            'processed_at' => now(),
            'processed_by' => auth()->user()->name ?? auth()->id(),
        ]);

        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Payroll run marked as paid.']);
        $this->run->refresh();
    }

    public function confirmCancel(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Cancel Payroll Run?',
            'message' => 'This action cannot be undone. Are you sure?',
            'confirmEvent' => 'cancelRun',
            'confirmParams' => [],
        ]);
    }

    public function cancel(): void
    {
        if (!in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review', 'approved'])) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'This payroll run cannot be cancelled.']);
            return;
        }

        $this->run->update(['status' => 'cancelled']);
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Payroll run cancelled.']);
        $this->run->refresh();
    }

    public function confirmRecalculate(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Recalculate Payroll?',
            'message' => 'This will overwrite existing payslip calculations. Continue?',
            'confirmEvent' => 'recalculate',
            'confirmParams' => [],
        ]);
    }

    public function recalculate(): void
    {
        if ($this->run->status !== 'draft') {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Only draft runs can be recalculated.']);
            return;
        }
        app(PayrollCalculator::class)->calculate($this->run);
        $this->run->refresh();
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Recalculation completed.']);
    }

    public function exportPayslips(): mixed
    {
        return redirect()->route('payroll.payslips.export', ['payroll_run_id' => $this->run->id, 'format' => 'pdf']);
    }

    public function render()
    {
        $canApprove = in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review']);
        $canMarkPaid = $this->run->status === 'approved';
        $canCancel = in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review', 'approved']);
        $canRecalculate = $this->run->status === 'draft';

        return view('hr::livewire.payroll.payroll-run-detail', [
            'canApprove' => $canApprove,
            'canMarkPaid' => $canMarkPaid,
            'canCancel' => $canCancel,
            'canRecalculate' => $canRecalculate,
        ]);
    }
}