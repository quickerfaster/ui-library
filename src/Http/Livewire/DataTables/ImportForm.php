<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use Livewire\Component;
use Livewire\WithFileUploads;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Jobs\ProcessImport;
use QuickerFaster\UILibrary\Models\Import;
use Maatwebsite\Excel\Facades\Excel;
use QuickerFaster\UILibrary\Models\ImportChunk;
use Illuminate\Support\Facades\Storage;


class ImportForm extends Component
{
    use WithFileUploads;

    public string $configKey;
    public ?string $modalId = null;

    // File upload
    public $file;
    public bool $hasHeaderRow = true;
    public array $columnMapping = [];

    // Preview data
    public array $previewHeaders = [];
    public array $previewRows = [];
    public ?int $importId = null;
    public ?string $error = null;

    // NEW: store import result to display in modal
    public ?array $importResult = null;
    public $runInBackground = false;


    public ?int $activeImportId = null;

    public string $importStatus = '';
    public int $completedChunks = 0;
    public int $totalChunks = 0;
    public int $totalRows = 0;
    public int $successfulRows = 0;
    public int $failedRows = 0;
    public ?string $errorFileUrl = null;

    protected $pollingInterval = 2000;


    protected $listeners = [
        'importCompleted' => 'handleImportCompleted',
    ];

    public function mount(string $configKey, string $modalId)
    {
        $this->configKey = $configKey;
        $this->modalId = $modalId;
        $this->resetImportState();
    }

    public function resetImportState(): void
    {
        $this->importId = null;
        $this->importStatus = '';
        $this->completedChunks = 0;
        $this->totalChunks = 0;
        $this->totalRows = 0;
        $this->successfulRows = 0;
        $this->failedRows = 0;
        $this->errorFileUrl = null;
    }


    public function cancelImport()
    {
        if ($this->importId) {
            $import = Import::find($this->importId);
            if ($import && in_array($import->status, ['pending', 'processing'])) {
                $import->update(['status' => 'cancelled']);
                // Optionally delete any partial chunks
                ImportChunk::where('import_id', $this->importId)->delete();

                // Delete the entire import directory (including partial chunks)
                $importDir = "imports/{$import->id}";
                if (Storage::disk('local')->exists($importDir)) {
                    Storage::disk('local')->deleteDirectory($importDir);
                }

            }
            $this->importStatus = 'cancelled';
            // $this->dispatch('showAlert', ['type' => 'info', 'message' => 'Import cancelled.']);
        }
    }


    public function getImportProgressProperty()
    {
        if (!$this->importId)
            return null;
        $import = Import::find($this->importId);
        if (!$import)
            return null;

        $this->importStatus = $import->status;
        $this->totalChunks = $import->total_chunks ?? 0;
        $this->totalRows = $import->total_rows ?? 0;
        $this->successfulRows = $import->successful_rows ?? 0;
        $this->failedRows = $import->failed_rows ?? 0;

        if ($this->totalChunks > 0) {
            $this->completedChunks = ImportChunk::where('import_id', $import->id)
                ->whereIn('status', ['completed', 'failed'])
                ->count();
        }

        return $import;
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
            'config_key' => $this->configKey,
            'file_path' => $path,
            'original_filename' => $this->file->getClientOriginalName(),
            'total_rows' => $this->getTotalRows(),
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        /*ProcessImport::dispatch($import->id, $this->columnMapping, $this->hasHeaderRow);
        $this->importId = $import->id;
        $this->importResult = null; // clear previous result */



        ProcessImport::dispatch($import->id, $this->columnMapping, $this->hasHeaderRow);
        $this->importId = $import->id;
        $this->importStatus = 'processing';
        $this->activeImportId = $import->id;
        $this->dispatch('startImportPolling', $import->id);


        if ($this->runInBackground) {
            $this->dispatch('closeModal')->to('qf.import-modal');
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Import started. You will be notified when complete.']);
        } else {
            // Keep modal open, show progress spinner (existing behaviour)
            $this->importId = $import->id;
            $this->importResult = null; // clear previous result 
        }

    }


    public function checkImportStatus()
    {
        if (!$this->importId) {
            return;
        }

        $import = Import::find($this->importId);
        if (!$import) {
            \Log::error('Import not found', ['id' => $this->importId]);
            $this->importId = null;
            return;
        }



        $this->importStatus = $import->status;
        $this->totalRows = $import->total_rows ?? 0;
        $this->successfulRows = $import->successful_rows ?? 0;
        $this->failedRows = $import->failed_rows ?? 0;
        $this->totalChunks = $import->total_chunks ?? 0;

        if ($this->totalChunks > 0) {
            $this->completedChunks = ImportChunk::where('import_id', $import->id)
                ->whereIn('status', ['completed', 'failed'])
                ->count();
        }

        if ($import->error_file) {
            $this->errorFileUrl = route('import.download-errors', $import);
        }






        // Still processing or pending? Wait.
        if (in_array($import->status, ['pending', 'processing'])) {
            return; // do nothing, polling will retry
        }


        // Now status is either 'completed' or 'failed'
        if ($import->status === 'completed') {
            $result = [
                'successful' => $import->successful_rows ?? 0,
                'failed' => $import->failed_rows ?? 0,
            ];
            if ($import->error_file) {
                $result['errorFileUrl'] = route('import.download-errors', $import);
            }
            $this->importResult = $result;
            $this->dispatch('refreshDataTable');
        } elseif ($import->status === 'failed') {
            $errors = json_decode($import->errors, true);
            $errorMsg = is_array($errors) ? implode('; ', $errors) : 'Unknown error';
            $this->importResult = [
                'failed' => true,
                'error' => $errorMsg,
            ];
        } else {
            // Should never happen
            \Log::error('Unexpected import status', ['id' => $import->id, 'status' => $import->status]);
            $this->importResult = [
                'failed' => true,
                'error' => "Unexpected status: {$import->status}",
            ];
        }

        // Stop polling after we have a result
        $this->importId = null;
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
        // This method might be used by Livewire events if you prefer event-based completion
        // We use polling, so it's not essential. Keep for compatibility.
    }

    public function render()
    {
        return view('qf::livewire.data-tables.import-form', [
            'importResult' => $this->importResult,
        ]);
    }
}