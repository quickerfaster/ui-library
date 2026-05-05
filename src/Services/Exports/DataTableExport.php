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
        // Remove empty strings and trim each column name
        $this->columns = array_filter(array_map('trim', $columns), fn($col) => $col !== '');
    }

    public function collection()
    {
        // Convert Eloquent models to arrays to avoid serialization issues
        $records = $this->records;

        if (empty($this->columns)) {
            return $records->map(function ($item) {
                return $item instanceof \Illuminate\Database\Eloquent\Model ? $item->toArray() : $item;
            });
        }

        return $records->map(function ($record) {
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