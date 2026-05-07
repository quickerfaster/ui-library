<?php

namespace QuickerFaster\UILibrary\Services\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Traits\ResolvesExportValues;

class DataTableExport implements FromCollection, WithHeadings, WithMapping
{

    use ResolvesExportValues;

    protected string $configKey;
    protected $records;
    protected array $columns;
    protected array $fieldDefinitions;

    public function __construct(string $configKey, $records, array $columns = [])
    {
        $this->configKey = $configKey;
        $this->records = $records;
        $this->columns = $columns;

        $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
        $this->fieldDefinitions = $resolver->getFieldDefinitions();
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        $headings = [];
        $columnsToUse = !empty($this->columns) ? $this->columns : array_keys($this->fieldDefinitions);

        foreach ($columnsToUse as $field) {
            $headings[] = $this->fieldDefinitions[$field]['label'] ?? ucfirst($field);
        }
        return $headings;
    }

    public function map($record): array
    {
        $columnsToUse = !empty($this->columns) ? $this->columns : array_keys($this->fieldDefinitions);
        $row = [];
        foreach ($columnsToUse as $field) {
            $row[] = $this->getFieldValueForExport($record, $field, $this->fieldDefinitions);
        }
        return $row;
    }




}