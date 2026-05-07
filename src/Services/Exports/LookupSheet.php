<?php

namespace QuickerFaster\UILibrary\Services\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class LookupSheet implements FromArray, WithHeadings, WithTitle
{
    protected string $name;
    protected array $data;

    public function __construct(string $name, array $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    public function headings(): array
    {
        return ['ID', 'Display Value'];
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        // Excel sheet names have 31 char limit
        return substr('Lookup_' . $this->name, 0, 31);
    }
}