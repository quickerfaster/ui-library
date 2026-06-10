<?php

namespace QuickerFaster\UILibrary\Services\BankFiles;

use App\Modules\Hr\Models\PayrollRun;

interface BankFileGenerator
{
    public function generate(PayrollRun $run): string;
    public function getFileName(PayrollRun $run): string;
    public function getMimeType(): string;
}