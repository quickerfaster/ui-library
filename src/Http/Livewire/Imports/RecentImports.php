<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Imports;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\Import;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RecentImports extends Component
{
    public $recentImports = [];
    public $inProgressImports = [];
    public $dropdownOpen = false;

    public bool $embedded = false; // add this



    protected $listeners = [
        'refreshImports' => 'loadImports',
        'clearAllImportsConfirmed' => 'performClearAll',

    ];

 


    public function mount(bool $embedded = false)
    {
        $this->embedded = $embedded;
        $this->loadImports();
    }

    public function loadImports()
    {
        $userId = Auth::id();
        $sessionKey = "import_notified_{$userId}";
        $notifiedIds = session($sessionKey, []);

        // Completed/failed imports (for dropdown list)
        $this->recentImports = Import::where('user_id', $userId)
            ->whereIn('status', ['completed', 'failed'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // In‑progress imports (pending/processing)
        $this->inProgressImports = Import::where('user_id', $userId)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Notifications for newly completed imports (same as before)
        $currentIds = $this->recentImports->pluck('id')->toArray();
        $newIds = array_diff($currentIds, $notifiedIds);
        foreach ($newIds as $id) {
            $import = Import::find($id);
            if ($import) {
                if ($import->failed_rows > 0) {
                    $this->dispatch('showAlert', [
                        'type' => 'warning',
                        'message' => "Import completed with {$import->failed_rows} errors. Check the Imports dropdown to download the error report.",
                    ]);
                } else {
                    $this->dispatch('showAlert', [
                        'type' => 'success',
                        'message' => "Import completed: {$import->successful_rows} records imported.",
                    ]);
                }
                $notifiedIds[] = $id;
            }
        }
        session([$sessionKey => $notifiedIds]);
    }

    public function getInProgressCountProperty()
    {
        return $this->inProgressImports->count();
    }

    public function toggleDropdown()
    {
        $this->dropdownOpen = !$this->dropdownOpen;
        if ($this->dropdownOpen) {
            $this->loadImports();
        }
    }

    public function closeDropdown()
    {
        $this->dropdownOpen = false;
    }

    public function downloadErrorReport($importId)
    {
        $import = Import::find($importId);
        if ($import && $import->error_file) {
            return redirect()->route('import.download-errors', $import);
        }
    }

    /**
     * Cancel an in‑progress import.
     */
    public function cancelImport($importId)
    {
        $import = Import::where('id', $importId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$import || !in_array($import->status, ['pending', 'processing'])) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Cannot cancel this import.']);
            return;
        }

        // Mark as cancelled
        $import->update([
            'status' => 'cancelled',
            'error_message' => 'Cancelled by user',
        ]);

        // Delete the uploaded file if exists
        if ($import->file_path && Storage::disk('local')->exists($import->file_path)) {
            Storage::disk('local')->delete($import->file_path);
        }
        // Delete any partial error file
        if ($import->error_file && Storage::disk('local')->exists($import->error_file)) {
            Storage::disk('local')->delete($import->error_file);
            $import->update(['error_file' => null]);
        }

        $this->loadImports(); // refresh dropdown
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Import cancelled.']);
    }

    public function confirmClearAll()
    {

        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Clear All Imports?',
            'message' => 'This will permanently delete all import records and their associated files. This action cannot be undone.',
            'icon' => 'fas fa-trash-alt text-danger',
            'size' => 'sm',
            'confirmEvent' => 'clearAllImportsConfirmed',
            'confirmParams' => [],
        ]);
    }

    public function performClearAll()
    {
        $userId = Auth::id();
        $imports = Import::where('user_id', $userId)
            ->whereIn('status', ['completed', 'failed', 'cancelled'])
            ->get();

        foreach ($imports as $import) {
            if ($import->error_file && Storage::disk('local')->exists($import->error_file)) {
                Storage::disk('local')->delete($import->error_file);
            }
            if ($import->file_path && Storage::disk('local')->exists($import->file_path)) {
                Storage::disk('local')->delete($import->file_path);
            }
            $import->delete();
        }

        $sessionKey = "import_notified_{$userId}";
        session()->forget($sessionKey);

        $this->loadImports();
        $this->dropdownOpen = false;
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Cleared all imports and deleted files.']);
    }

    public function render()
    {
        return view('qf::livewire.imports.recent-imports', [
            'inProgressImports' => $this->inProgressImports,
        ]);
    }
}