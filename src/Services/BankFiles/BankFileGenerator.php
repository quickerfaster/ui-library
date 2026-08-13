<?php

namespace QuickerFaster\UILibrary\Services\BankFiles;

/**
 * Interface for bank file generators.
 *
 * The $run parameter should be an object that provides:
 * - $run->id
 * - $run->payslips (collection with: net_pay, employee->payrollProfile, employee->full_name)
 * - $run->period_start / $run->period_end (Carbon instances)
 * - $run->total_cash_required
 */
interface BankFileGenerator
{
    public function generate($run): string;
    public function getFileName($run): string;
    public function getMimeType(): string;
}