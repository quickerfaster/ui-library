<?php

namespace QuickerFaster\UILibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Services\Imports\ImportProcessor;
use QuickerFaster\UILibrary\Models\Import;

class ProcessImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $importId;
    public $columnMapping;
    public $hasHeaderRow;

    public function __construct(int $importId, array $columnMapping, bool $hasHeaderRow)
    {
        $this->importId = $importId;
        $this->columnMapping = $columnMapping;
        $this->hasHeaderRow = $hasHeaderRow;
    }

    public function handle()
    {
        $import = Import::find($this->importId);
        if (!$import) {
            return;
        }

        $import->update(['status' => 'processing']);

        try {
            $configResolver = new ConfigResolver($import->config_key);
            $fieldFactory = new FieldFactory();
            $processor = new ImportProcessor($configResolver, $fieldFactory);

            $result = $processor->process(
                $import->file_path,
                $this->columnMapping,
                $this->hasHeaderRow
            );

            $import->update([
                'status'          => 'completed',
                'processed_rows'  => $result['processed'],
                'successful_rows' => $result['successful'],
                'failed_rows'     => $result['failed'],
                'errors'          => json_encode($result['errors']),
            ]);

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'errors' => json_encode([$e->getMessage()]),
            ]);
        }
    }
}