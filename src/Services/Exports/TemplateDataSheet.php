<?php

namespace QuickerFaster\UILibrary\Services\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TemplateDataSheet implements FromArray, WithHeadings, WithTitle
{
    protected array $fieldNames;
    protected array $exampleRow;

    public function __construct(array $fieldNames, array $exampleRow)
    {
        $this->fieldNames = $fieldNames;
        $this->exampleRow = $exampleRow;
    }

    public function headings(): array
    {
        return $this->fieldNames;
    }

    public function array(): array
    {
        if (empty($this->exampleRow)) {
            return [];
        }
        return [$this->exampleRow];
    }

    public function title(): string
    {
        return 'Data Template';
    }
}