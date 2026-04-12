<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class SetupChecklist extends Component
{
    public array $items = [];
    public array $status = [];       // true = completed, false = pending
    public int $completedCount = 0;

    protected $listeners = [
        'formSaved' => 'refreshStatus',   // update after any modal save
    ];

    public function mount(): void
    {
        $this->items = config('app_setup.items', []);
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $this->status = [];
        foreach ($this->items as $index => $item) {
            $modelClass = $item['model'];
            // Check if at least one record exists
            $exists = $modelClass::query()->exists();
            $this->status[$index] = $exists;
        }
        $this->completedCount = count(array_filter($this->status));
    }


public $test = 0;

public function increment()
{
    $this->test++;
}
    
    /**
     * Handle click on a checklist item.
     */
    public function handleItemClick(int $index): void
    {

        $item = $this->items[$index];
        $configKey = $item['configKey'];
        $modelClass = $item['model'];
        $groups = $item['groups'] ?? [];   // get groups from config


        // If a record already exists, find its ID to edit
        if ($this->status[$index]) {
            $firstRecord = $modelClass::query()->first();
            if ($firstRecord) {
                $this->dispatch('openEditModal', configKey: $configKey, recordId: $firstRecord->id, allowedGroups: $groups);
                return;
            }
        }

        // Otherwise, open add modal
        $this->dispatch('openAddModal', configKey: $configKey, allowedGroups: $groups);
    }


    public function markComplete()
    {
        $setting = \App\Models\SystemSetting::first();
        if ($setting) {
            $setting->setup_completed = true;
            $setting->save();
        }
        return redirect()->to('/dashboard');
    }




    public function render()
    {
        return view('qf::livewire.setup-checklist');
    }
}