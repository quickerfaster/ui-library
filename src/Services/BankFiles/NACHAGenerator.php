<?php

namespace QuickerFaster\UILibrary\Services\BankFiles;

use App\Modules\Hr\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

class NACHAGenerator implements BankFileGenerator
{
    protected string $companyName = 'YOUR COMPANY INC'; // TODO: make configurable
    protected string $companyId = '123456789';         // TODO: from company settings
    protected string $originRouting = '021000021';     // TODO: from company bank account
    protected string $originId = '123456789';          // TODO: from company

    public function generate(PayrollRun $run): string
    {
        $lines = [];

        // File Header Record (1)
        $lines[] = $this->formatFileHeader();

        // Company/Batch Header Record (5)
        $lines[] = $this->formatBatchHeader($run);

        foreach ($run->payslips as $payslip) {
            $employee = $payslip->employee;
            $profile = $employee->payrollProfile;
            if (!$profile || !$profile->bank_account_number || !$profile->bank_routing_number) {
                continue; // skip missing bank details – you might want to log
            }

            // Entry Detail Record (6)
            $lines[] = $this->formatEntryDetail($payslip, $profile);
        }

        // Batch Control Record (8)
        $lines[] = $this->formatBatchControl($run);

        // File Control Record (9)
        $lines[] = $this->formatFileControl($run);

        return implode("\r\n", $lines);
    }

    protected function formatFileHeader(): string
    {
        return sprintf(
            "101 %s %s %-10s %-6s %-10s %-10s %s",
            $this->originRouting,
            $this->companyId,
            now()->format('md'),
            now()->format('Hi'),
            'A',
            '094',
            '1'
        );
    }

    protected function formatBatchHeader(PayrollRun $run): string
    {
        return sprintf(
            "5 %s %-16s %-20s %-16s %-10s %-10s %-8s %-10s %-10s %-10s %-10s %-10s %-10s",
            $this->originRouting,
            $this->companyName,
            'PPD',
            $run->period_start->format('Ymd'),
            $run->period_end->format('Ymd'),
            '1',
            '1',
            '1',
            '1',
            '1',
            '1',
            '1',
            '1'
        );
    }

    protected function formatEntryDetail($payslip, $profile): string
    {
        $amount = (int) round($payslip->net_pay * 100); // cents
        $routing = str_pad($profile->bank_routing_number, 9, '0', STR_PAD_LEFT);
        $account = str_pad($profile->bank_account_number, 17, ' ', STR_PAD_RIGHT);
        $name = str_pad(substr($profile->bank_account_name ?? $employee->full_name, 0, 22), 22, ' ');

        return sprintf(
            "6 %s %s %s %s %-22s %-15s %-10s %-10s %-8s %-10s %-10s",
            $routing,
            $account,
            $amount,
            '01',
            $name,
            '',
            '',
            '',
            '',
            '',
            ''
        );
    }

    protected function formatBatchControl(PayrollRun $run): string
    {
        $totalAmount = (int) round($run->total_cash_required * 100);
        $entryCount = $run->payslips->count();
        return sprintf(
            "8 %d %d %s %s %-16s %-20s %-10s %-10s",
            $entryCount,
            $totalAmount,
            '',
            '',
            '',
            '',
            '',
            ''
        );
    }

    protected function formatFileControl(PayrollRun $run): string
    {
        $totalAmount = (int) round($run->total_cash_required * 100);
        $entryCount = $run->payslips->count();
        return sprintf(
            "9 %d %d %s %s %-10s %-10s",
            $entryCount,
            $totalAmount,
            '',
            '',
            '',
            ''
        );
    }

    public function getFileName(PayrollRun $run): string
    {
        return "nacha_{$run->id}_{$run->period_end->format('Ymd')}.ach";
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }
}