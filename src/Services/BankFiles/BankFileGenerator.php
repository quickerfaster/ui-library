<?php

namespace QuickerFaster\UILibrary\Services\BankFiles;

/**
 * Interface for bank file generators.
 *
 * The $run parameter should be an object that provides:
 * - $run->id
 * - $run->payments (collection with: amount, recipient->bankAccount, recipient->full_name)
 * - $run->start_date / $run->end_date (Carbon instances)
 * - $run->total_amount
 */
interface BankFileGenerator
{
    public function generate($run): string;
    public function getFileName($run): string;
    public function getMimeType(): string;
}
