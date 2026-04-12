<?php

namespace QuickerFaster\UILibrary\Http\Livewire\AccessControls;

use Livewire\Component;
use App\Modules\Admin\Models\Role;
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
        // Get all module directories
        $modules = File::directories(base_path('app/Modules'));

        // Loop through each module to load views, routes, and config files dynamically
        foreach ($modules as $module) {
            $moduleNames[] = basename($module); // Get the module name from the directory
        }

        return $moduleNames;
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
