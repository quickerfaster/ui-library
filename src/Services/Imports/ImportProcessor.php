<?php

namespace QuickerFaster\UILibrary\Services\Imports;

use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ImportProcessor
{
    protected ConfigResolver $configResolver;
    protected FieldFactory $fieldFactory;

    public function __construct(ConfigResolver $configResolver, FieldFactory $fieldFactory)
    {
        $this->configResolver = $configResolver;
        $this->fieldFactory = $fieldFactory;
    }

    public function process(string $filePath, array $columnMapping, bool $hasHeaderRow): array
    {
        $modelClass = $this->configResolver->getModel();
        $fieldDefinitions = $this->configResolver->getFieldDefinitions();

        $rows = Excel::toArray([], $filePath)[0];

        if ($hasHeaderRow) {
            array_shift($rows); // remove header row
        }

        $processed = 0;
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $row) {
            $processed++;
            $data = [];

            // Build data array using column mapping
            foreach ($columnMapping as $field => $columnIndex) {
                if ($columnIndex !== '' && isset($row[$columnIndex])) {
                    $data[$field] = $row[$columnIndex];
                }
            }

            // Validate row using field definitions
            $rules = [];
            foreach ($fieldDefinitions as $field => $def) {
                if (($def['fillable'] ?? false) && isset($data[$field])) {
                    $fieldObj = $this->fieldFactory->make($field, $def);
                    $rules[$field] = $fieldObj->getValidationRules();
                }
            }

            // $validator = Validator::make($data, $rules);
// Option 1: Flatten the rules before the loop
$flatRules = array_map(function($rule) {
    return is_array($rule) ? implode('|', $rule) : $rule;
}, $rules);

// Then use the flat rules
$validator = Validator::make($data, $flatRules);

            
            if ($validator->fails()) {
                $failed++;
                $errors[] = [
                    'row'    => $rowIndex + ($hasHeaderRow ? 2 : 1),
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            try {
                $modelClass::create($data);
                $successful++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'row'    => $rowIndex + ($hasHeaderRow ? 2 : 1),
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        return [
            'processed'  => $processed,
            'successful' => $successful,
            'failed'     => $failed,
            'errors'     => $errors,
        ];
    }
}