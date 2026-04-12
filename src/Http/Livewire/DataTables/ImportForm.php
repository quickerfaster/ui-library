<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use Livewire\Component;
use Livewire\WithFileUploads;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Jobs\ProcessImport;
use QuickerFaster\UILibrary\Models\Import;
use Maatwebsite\Excel\Facades\Excel;

class ImportForm extends Component
{
    use WithFileUploads;

    public string $configKey;
    public ?string $modalId = null; // To close modal when done

    // File upload
    public $file;
    public bool $hasHeaderRow = true;
    public array $columnMapping = [];

    // Preview data
    public array $previewHeaders = [];
    public array $previewRows = [];
    public ?int $importId = null;
    public ?string $error = null;

public $importStatus = null;

    protected $listeners = [
        'importCompleted' => 'handleImportCompleted',
    ];

    public function mount(string $configKey, ?string $modalId = null)
    {
        $this->configKey = $configKey;
        $this->modalId = $modalId;
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        try {
            $this->parseFilePreview();
        } catch (\Exception $e) {
            $this->error = 'Failed to parse file: ' . $e->getMessage();
        }
    }

    protected function parseFilePreview()
    {
        $path = $this->file->getRealPath();
        $rows = Excel::toArray([], $path)[0];

        if (empty($rows)) {
            $this->previewHeaders = [];
            $this->previewRows = [];
            return;
        }

        if ($this->hasHeaderRow) {
            $this->previewHeaders = array_shift($rows);
            $this->previewRows = array_slice($rows, 0, 5);
        } else {
            $this->previewHeaders = [];
            $this->previewRows = array_slice($rows, 0, 5);
        }

        $this->autoMapColumns();
    }

    protected function autoMapColumns(): void
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $fieldDefinitions = $resolver->getFieldDefinitions();
        $fillableFields = array_keys(array_filter($fieldDefinitions, fn($def) => ($def['fillable'] ?? false) === true));

        
        $mapping = [];
        if ($this->hasHeaderRow && !empty($this->previewHeaders)) {
            foreach ($this->previewHeaders as $index => $header) {
                $headerClean = strtolower(trim($header));
                foreach ($fillableFields as $field) {
                    if (strtolower($field) === $headerClean) {
                        $mapping[$field] = $index;
                        break;
                    }
                }
            }
        } else {
            // Simple heuristic: if column count matches fillable fields, map in order
            if (!empty($this->previewRows) && count($fillableFields) === count($this->previewRows[0])) {
                foreach ($fillableFields as $i => $field) {
                    $mapping[$field] = $i;
                }
            }
        }
        $this->columnMapping = $mapping;
    }

    public function startImport()
    {
        $this->validate([
            'file' => 'required',
        ]);

        $path = $this->file->store('imports', 'local');

        $import = Import::create([
            'config_key'        => $this->configKey,
            'file_path'         => $path,
            'original_filename' => $this->file->getClientOriginalName(),
            'total_rows'        => $this->getTotalRows(),
            'user_id'           => auth()->id(),
            'status'            => 'pending',
        ]);

        ProcessImport::dispatch($import->id, $this->columnMapping, $this->hasHeaderRow);

        $this->importId = $import->id;

        /*$this->dispatch('showAlert', [
            'type'    => 'info',
            'message' => 'Import started. You will be notified when complete.',
        ]);*/

        // Close the modal
        /*if ($this->modalId) {
            $this->dispatch('closeModal')->to('qf.import-modal');
        }*/
    }



/**
 * Polling method to check import status.
 */
public function checkImportStatus()
{
    if (!$this->importId) {
        return;
    }

    $import = Import::find($this->importId);
    if (!$import || $import->status === 'pending') {
        return;
    }

    // Import is no longer pending (completed or failed)
    $this->importStatus = $import->status;

    if ($import->status === 'completed') {
        $this->dispatch('showAlert', [
            'type'    => 'success',
            'message' => "Import completed: {$import->successful_rows} records imported, {$import->failed_rows} failed.",
        ]);
        $this->dispatch('refreshDataTable'); // Optional: refresh table
        
    } elseif ($import->status === 'failed') {
        $errors = json_decode($import->errors, true);
        $errorMsg = is_array($errors) ? implode('; ', $errors) : 'Unknown error';
        $this->dispatch('showAlert', [
            'type'    => 'error',
            'message' => "Import failed: {$errorMsg}",
        ]);
    }

    // Stop polling by resetting importId
    $this->importId = null;
    $this->dispatch('closeModal')->to('qf.import-modal');

}





    protected function getTotalRows(): int
    {
        $path = $this->file->getRealPath();
        $rows = Excel::toArray([], $path)[0];
        $total = count($rows);
        return $this->hasHeaderRow ? max(0, $total - 1) : $total;
    }

    public function handleImportCompleted(array $payload)
    {
        $importId = $payload['importId'];
        $success = $payload['success'];
        $message = $payload['message'] ?? '';

        if ($success) {
            $this->dispatch('showAlert', [
                'type'    => 'success',
                'message' => $message ?: 'Import completed successfully.',
            ]);
            $this->dispatch('refreshDataTable')->to('qf.data-table'); // Optionally refresh table
        } else {
            $this->dispatch('showAlert', [
                'type'    => 'error',
                'message' => $message ?: 'Import failed. Check logs for details.',
            ]);
        }
    }

    public function render()
    {
        return view('qf::livewire.data-tables.import-form');
    }
}