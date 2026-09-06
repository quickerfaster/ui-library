<?php

namespace QuickerFaster\UILibrary\Services\BankFiles;

use Illuminate\Support\Facades\DB;

class NACHAGenerator implements BankFileGenerator
{
    protected string $companyName = 'YOUR COMPANY INC'; // TODO: make configurable
    protected string $companyId = '123456789';         // TODO: from company settings
    protected string $originRouting = '021000021';     // TODO: from company bank account
    protected string $originId = '123456789';          // TODO: from company

    public function generate($run): string
    {
        $lines = [];

        // File Header Record (1)
        $lines[] = $this->formatFileHeader();

        // Company/Batch Header Record (5)
        $lines[] = $this->formatBatchHeader($run);

        foreach ($run->payments as $payment) {
            $recipient = $payment->recipient;
            $bankAccount = $recipient->bankAccount;
            if (!$bankAccount || !$bankAccount->bank_account_number || !$bankAccount->bank_routing_number) {
                continue; // skip missing bank details – you might want to log
            }

            // Entry Detail Record (6)
            $lines[] = $this->formatEntryDetail($payment, $bankAccount);
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

    protected function formatBatchHeader($run): string
    {
        return sprintf(
            "5 %s %-16s %-20s %-16s %-10s %-10s %-8s %-10s %-10s %-10s %-10s %-10s %-10s",
            $this->originRouting,
            $this->companyName,
            'PPD',
            $run->start_date->format('Ymd'),
            $run->end_date->format('Ymd'),
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

    protected function formatEntryDetail($payment, $bankAccount): string
    {
        $amount = (int) round($payment->amount * 100); // cents
        $routing = str_pad($bankAccount->bank_routing_number, 9, '0', STR_PAD_LEFT);
        $account = str_pad($bankAccount->bank_account_number, 17, ' ', STR_PAD_RIGHT);
        $name = str_pad(substr($bankAccount->bank_account_name ?? $payment->recipient->full_name, 0, 22), 22, ' ');

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

    protected function formatBatchControl($run): string
    {
        $totalAmount = (int) round($run->total_amount * 100);
        $entryCount = $run->payments->count();
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

    protected function formatFileControl($run): string
    {
        $totalAmount = (int) round($run->total_amount * 100);
        $entryCount = $run->payments->count();
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

    public function getFileName($run): string
    {
        return "nacha_{$run->id}_{$run->end_date->format('Ymd')}.ach";
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }
}
