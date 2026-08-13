<?php

namespace QuickerFaster\UILibrary\Http\Livewire\AccessControls;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\Role;
use Illuminate\Support\Facades\File;

class ModuleSelector extends Component
{
    public $roles = [];
    public $scopes = ["Role", "User", "Team"];
    public $modules = [];
    public $selectedScope = null;
    public $selectedRole = null;
    public $selectedModule = null;

    public function mount()
    {
        // Define or fetch roles and modules dynamically
        $this->roles = Role::all()->pluck("id", "name");
        $this->modules = $this->getModuleNames();

        // ["Role", "User", "Team"];
        $this->selectedScope = $this->scopes[0]; // For now, always "Role" is choosen
    }



    private function getModuleNames() {
        $moduleNames = [];

        // Scan business modules path from config
        $businessPath = config('ui-library.module_paths.business', base_path('app/Modules'));
        if (is_dir($businessPath)) {
            $modules = File::directories($businessPath);
            foreach ($modules as $module) {
                $moduleNames[] = basename($module);
            }
        }

        // Also scan core modules path
        $corePath = config('ui-library.module_paths.core');
        if ($corePath && is_dir($corePath)) {
            $coreModules = File::directories($corePath);
            foreach ($coreModules as $module) {
                $moduleNames[] = basename($module);
            }
        }

        return array_unique($moduleNames);
    }



    public function navigate()
    {
        if ($this->selectedRole && $this->selectedModule) {

            $data = [
                'module' => strtolower($this->selectedModule),
                'scope' => strtolower($this->selectedScope),
                'id' => intval($this->selectedRole),
            ];

            $this->dispatch("updateAccessControlParametersEvent", $data);
            //return redirect()->route('access-control.manage', $data);
        }
    }







    public function render()
    {
        //return view('livewire.module-selector');
        return view('admin.views::access-controls.module-selector');

    }
}
