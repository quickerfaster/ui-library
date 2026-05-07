<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Exports;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\Export;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RecentExports extends Component
{
    public $completedExports = [];
    public $inProgressExports = [];
    public $dropdownOpen = false;

    protected $listeners = [
        'refreshExports' => 'loadExports',
        'clearAllExportsConfirmed' => 'performClearAllExports',
    ];

    public function mount()
    {
        $this->loadExports();
    }

    public function loadExports()
    {
        $userId = Auth::id();
        $this->completedExports = Export::where('user_id', $userId)
            ->whereIn('status', ['completed', 'failed'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $this->inProgressExports = Export::where('user_id', $userId)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getInProgressCountProperty()
    {
        return $this->inProgressExports->count();
    }

    public function toggleDropdown()
    {
        $this->dropdownOpen = !$this->dropdownOpen;
        if ($this->dropdownOpen) {
            $this->loadExports();
        }
    }

    public function closeDropdown()
    {
        $this->dropdownOpen = false;
    }

    /**
     * Show confirmation dialog before clearing all exports.
     */
    public function confirmClearAllExports()
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Clear All Exports?',
            'message' => 'This will permanently delete all exported files and remove them from the list. This action cannot be undone.',
            'icon' => 'fas fa-trash-alt text-danger',
            'size' => 'sm',
            'confirmEvent' => 'clearAllExportsConfirmed',
            'confirmParams' => [],
        ]);
    }

    /**
     * Perform the actual deletion after confirmation.
     */
    public function performClearAllExports()
    {
        $exports = Export::where('user_id', Auth::id())
            ->whereIn('status', ['completed', 'failed'])
            ->get();

        foreach ($exports as $export) {
            // Delete physical file if exists
            if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
                Storage::disk('local')->delete($export->file_path);
            }
            $export->delete();
        }

        $this->loadExports();
        $this->dropdownOpen = false;

        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => 'Cleared all exports and deleted files.',
        ]);
    }


    public function cancelExport($exportId)
    {
        $export = Export::where('id', $exportId)
            ->where('user_id', auth()->id())
            ->first();

        if ($export && in_array($export->status, ['pending', 'processing'])) {
            $export->update(['status' => 'cancelled', 'error_message' => 'Cancelled by user']);
            // Optionally delete any partial file if it exists
            if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
                Storage::disk('local')->delete($export->file_path);
                $export->update(['file_path' => null]);
            }
            $this->loadExports(); // refresh dropdown
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Export cancelled.']);
        }
    }



    public function render()
    {
        return view('qf::livewire.exports.recent-exports');
    }
}