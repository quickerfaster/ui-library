<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Exports;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\Export;
use QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RecentExports extends Component
{
    public $completedExports = [];
    public $inProgressExports = [];
    public $dropdownOpen = false;
    public bool $embedded = false;


    protected $listeners = [
        'refreshExports' => 'loadExports',
        'clearAllExportsConfirmed' => 'performClearAllExports',
    ];




    public function mount(bool $embedded = false)
    {
        $this->embedded = $embedded;
        $this->loadExports();
    }

    public function loadExports()
    {
        $user = Auth::user();

        // Admin bypass: super_admin, admin, company_admin see all exports.
        // Other users only see their own exports (scoped by user_id).
        if (AuthorizationService::isBypassAllowed($user)) {
            $this->completedExports = Export::whereIn('status', ['completed', 'failed'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $this->inProgressExports = Export::whereIn('status', ['pending', 'processing'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $userId = $user->id;
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
        $user = Auth::user();
        $query = Export::whereIn('status', ['completed', 'failed']);

        // Non-admin users can only clear their own exports
        if (! AuthorizationService::isBypassAllowed($user)) {
            $query->where('user_id', $user->id);
        }

        $exports = $query->get();


        foreach ($exports as $export) {
            // Delete the directory and its contents
            $exportDir = "exports/{$export->id}";
            if (Storage::disk('local')->exists($exportDir)) {
                Storage::disk('local')->deleteDirectory($exportDir);
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
        $user = Auth::user();
        $query = Export::where('id', $exportId);

        // Non-admin users can only cancel their own exports
        if (! AuthorizationService::isBypassAllowed($user)) {
            $query->where('user_id', $user->id);
        }

        $export = $query->first();

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