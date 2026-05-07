<?php

namespace QuickerFaster\UILibrary\Services\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;

class TemplateExport implements WithMultipleSheets
{
    protected array $fieldNames;
    protected array $exampleRow;
    protected array $relationSheets;
    protected array $optionsReference;

public function __construct(
    array $fieldNames, 
    array $exampleRow = [], 
    array $relationSheets = [],
    array $optionsReference = []
) {
    $this->fieldNames = $fieldNames;
    $this->exampleRow = $exampleRow;
    $this->relationSheets = $relationSheets;
    $this->optionsReference = $optionsReference;
}

public function sheets(): array
{
    $sheets = [];
    $sheets[] = new TemplateDataSheet($this->fieldNames, $this->exampleRow);
    
    foreach ($this->relationSheets as $relationName => $data) {
        $sheets[] = new LookupSheet($relationName, $data);
    }
    
    if (!empty($this->optionsReference)) {
        $sheets[] = new OptionsReferenceSheet($this->optionsReference);
    }
    
    return $sheets;
}
}