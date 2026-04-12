<?php

namespace QuickerFaster\UILibrary\Services\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;

class DataTableExport implements FromCollection, WithHeadings
{
    protected string $configKey;
    protected $records;
    protected array $columns; // list of field names to export

    public function __construct(string $configKey, $records, array $columns = [])
    {
        $this->configKey = $configKey;
        $this->records = $records;
        $this->columns = $columns;
    }

    public function collection()
    {
        if (empty($this->columns)) {
            return $this->records;
        }

        // Map each record to only the selected columns
        return $this->records->map(function ($record) {
            $data = [];
            foreach ($this->columns as $field) {
                $data[$field] = data_get($record, $field);
            }
            return $data;
        });
    }

    public function headings(): array
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $definitions = $resolver->getFieldDefinitions();

        if (!empty($this->columns)) {
            $headings = [];
            foreach ($this->columns as $field) {
                $headings[] = $definitions[$field]['label'] ?? ucfirst($field);
            }
            return $headings;
        }

        // Default: all fields
        return array_keys($definitions);
    }
}