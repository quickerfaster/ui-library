<?php

namespace QuickerFaster\UILibrary\Services\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class OptionsReferenceSheet implements FromArray, WithHeadings, WithTitle
{
    protected array $referenceData;

    public function __construct(array $referenceData)
    {
        $this->referenceData = $referenceData;
    }

    public function headings(): array
    {
        return ['Field', 'Stored Key', 'Display Label'];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->referenceData as $field => $options) {
            foreach ($options as $key => $label) {
                $rows[] = [$field, $key, $label];
            }
        }
        return $rows;
    }

    public function title(): string
    {
        return 'Valid Options Reference';
    }
}