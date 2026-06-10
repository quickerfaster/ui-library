<?php

namespace QuickerFaster\UILibrary\Services\BankFiles;

use App\Modules\Hr\Models\PayrollRun;

class BACSGenerator implements BankFileGenerator
{
    protected string $serviceUserId = '123456';  // Your BACS service user number (6 digits)
    protected string $companyName = 'YOURCOMPANY';
    protected string $originatorId = '123456';   // Originator ID

    public function generate(PayrollRun $run): string
    {
        $lines = [];

        // Header record (Vol 1 – User Header)
        $lines[] = $this->formatUserHeader($run);

        // For each payslip, create a payment record
        $seq = 1;
        foreach ($run->payslips as $payslip) {
            $employee = $payslip->employee;
            $profile = $employee->payrollProfile;
            if (!$profile || !$profile->bank_account_number || !$profile->bank_sort_code) {
                continue;
            }

            $lines[] = $this->formatPaymentRecord($payslip, $profile, $seq++);
        }

        // Trailer record (Vol 1 – User Trailer)
        $lines[] = $this->formatUserTrailer($run, $seq - 1);

        return implode("\r\n", $lines);
    }

    protected function formatUserHeader(PayrollRun $run): string
    {
        return sprintf(
            "VOL1%6s%-18s%-4s%-18s%-2s",
            $this->serviceUserId,
            $this->companyName,
            'BACS',
            now()->format('Ymd'),
            '1'
        );
    }

    protected function formatPaymentRecord($payslip, $profile, int $seq): string
    {
        $sortCode = str_replace('-', '', $profile->bank_sort_code); // remove hyphens
        $account = str_pad($profile->bank_account_number, 8, '0', STR_PAD_LEFT);
        $amount = str_pad(round($payslip->net_pay * 100), 10, '0', STR_PAD_LEFT);
        $name = str_pad(substr($profile->bank_account_name ?? $payslip->employee->full_name, 0, 18), 18, ' ');
        $date = $run->payment_date?->format('dmy') ?? now()->addDays(3)->format('dmy');

        return sprintf(
            "PAY%6s%-8s%-8s%-4s%10s%-18s%-8s%-6s%-6s%-2s%02d%-6s%-6s",
            $this->originatorId,
            $sortCode,
            $account,
            '',
            $amount,
            $name,
            '',
            '',
            '',
            '',
            $seq,
            '',
            $date
        );
    }

    protected function formatUserTrailer(PayrollRun $run, int $count): string
    {
        $totalAmount = round($run->total_cash_required * 100);
        return sprintf(
            "END%6s%-6s%06d%011d%-14s%02d",
            $this->serviceUserId,
            '',
            $count,
            $totalAmount,
            '',
            1
        );
    }

    public function getFileName(PayrollRun $run): string
    {
        return "bacs_{$run->id}_{$run->period_end->format('Ymd')}.txt";
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }
}