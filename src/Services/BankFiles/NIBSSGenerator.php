<?php

namespace QuickerFaster\UILibrary\Services\BankFiles;

use App\Modules\Hr\Models\PayrollRun;

class NIBSSGenerator implements BankFileGenerator
{
    protected string $originatorId = '123456789012';  // 12-digit
    protected string $settlementBank = '000';         // Settlement bank code

    public function generate(PayrollRun $run): string
    {
        $lines = [];

        // Header record
        $lines[] = $this->formatHeader($run);

        // Detail records
        $seq = 1;
        foreach ($run->payslips as $payslip) {
            $employee = $payslip->employee;
            $profile = $employee->payrollProfile;
            if (!$profile || !$profile->bank_account_number || !$profile->bank_code) {
                continue;
            }

            $lines[] = $this->formatDetail($payslip, $profile, $seq++);
        }

        // Trailer record
        $lines[] = $this->formatTrailer($run, $seq - 1);

        return implode("\r\n", $lines);
    }

    protected function formatHeader(PayrollRun $run): string
    {
        return sprintf(
            "NIBSS%12s%4s%2s%2s%6s%8s",
            $this->originatorId,
            now()->format('md'),
            now()->format('H'),
            now()->format('i'),
            '1',
            ''
        );
    }

    protected function formatDetail($payslip, $profile, int $seq): string
    {
        $bankCode = str_pad($profile->bank_code, 3, '0', STR_PAD_LEFT);
        $account = str_pad($profile->bank_account_number, 10, '0', STR_PAD_LEFT);
        $amount = str_pad(round($payslip->net_pay), 12, '0', STR_PAD_LEFT);
        $name = str_pad(substr($profile->bank_account_name ?? $payslip->employee->full_name, 0, 30), 30, ' ');
        $narration = str_pad('PAYROLL-' . $run->id, 20, ' ', STR_PAD_RIGHT);

        return sprintf(
            "%3s%10s%012d%-30s%-20s%-8s%-2s%04d",
            $bankCode,
            $account,
            $amount,
            $name,
            $narration,
            '',
            '',
            $seq
        );
    }

    protected function formatTrailer(PayrollRun $run, int $count): string
    {
        $totalAmount = round($run->total_cash_required);
        return sprintf(
            "%03d%012d%-30s%06d%-8s%-2s",
            $count,
            $totalAmount,
            '',
            '1',
            '',
            ''
        );
    }

    public function getFileName(PayrollRun $run): string
    {
        return "nibss_{$run->id}_{$run->period_end->format('Ymd')}.txt";
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }
}