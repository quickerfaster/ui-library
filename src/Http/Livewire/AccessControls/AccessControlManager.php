<?php

namespace QuickerFaster\UILibrary\Http\Livewire\AccessControls;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use QuickerFaster\UILibrary\Models\Role;

use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use QuickerFaster\UILibrary\Services\AccessControl\AccessControlPermissionService;
use QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService;
use QuickerFaster\UILibrary\Services\System\ApplicationInfo;

class AccessControlManager extends Component
{


    public $moduleNames = [];
    public $showResourceControlButtonGroup = false;
    public $resourceNames = [];

    public $selectedScopeName = 'Role';
    public $scopeNames;
    public $selectedScope = null;
    public $selectedScopeId;
    public $selectedModule = null;
    public $selectedModuleName = null;


    public $isUrlAccess = false;


    public $controlList = ['view', 'create',  'edit', 'delete', 'print', 'export', 'import'];
    public $controlsCSSClasses = [
        'view' => ['color' => 'info', 'bg' => 'info', 'icon' => 'fas fa-eye'],
        'create' => ['color' => 'success', 'bg' => 'success', 'icon' => 'fas fa-plus'],
        'edit' => ['color' => 'warning', 'bg' => 'warning', 'icon' => 'fas fa-edit'],
        'delete' => ['color' => 'danger', 'bg' => 'danger', 'icon' => 'fas fa-trash'],

        'print' => ['color' => 'success', 'bg' => 'success', 'icon' => 'fas fa-print'],

        'export' => ['color' => 'primary', 'bg' => 'primary', 'icon' => 'fas fa-file-pdf'],
        'import' => ['color' => 'primary', 'bg' => 'primary', 'icon' => 'fas fa-file-import'],


        //'restore' => ['color' => 'success', 'bg' => 'success', 'icon' => 'fas fa-undo'],
        //'approve' => ['color' => 'success', 'bg' => 'success', 'icon' => 'fas fa-check'],
        //'reject' => ['color' => 'danger', 'bg' => 'danger', 'icon' => 'fas fa-times'],
        //'send' => ['color' => 'success', 'bg' => 'success', 'icon' => 'fas fa-paper-plane'],
        //'forceDelete' => ['color' => 'danger', 'bg' => 'danger', 'icon' => 'fas fa-trash'],
        //'archive' => ['color' => 'success', 'bg' => 'success', 'icon' => 'fas fa-archive'],
        //'unarchive' => ['color' => 'success', 'bg' => 'success', 'icon' => 'fas fa-archive'],


    ];


  

    public $resourceControlButtonGroup = [];




    protected $listeners = [
    ];


    public function mount() {

        $this->moduleNames = ApplicationInfo::getModuleNames();
        $selectedScopeClassName = match($this->selectedScopeName) {
            'Role' => \Spatie\Permission\Models\Role::class,
            'User' => \App\Models\User::class,
            default => "App\\Models\\".$this->selectedScopeName,
        };
        $this->scopeNames =  $selectedScopeClassName::all()->pluck("name", "id");

        
        if (strtolower($this->selectedScopeName) == "role")
            $this->scopeNames = array_diff($this->scopeNames->toArray(), AuthorizationService::ADMIN_ROLES_ARRAY);
       
    }






    public function updatedSelectedScopeId($id) {

        $this->updateSelectedScope();
        $this->showResourceControlButtonGroup = false;
        //$this->selectedModule = null;
     }


     public function updatedSelectedModule($module) {
        $this->showResourceControlButtonGroup = false;
     }


     protected function updateSelectedScope() {
        if ($this->selectedScopeName == 'Role') {
            //$data['scope'] = Role::with('team')->with('permissions')->findOrFail($id);
            $this->selectedScope = Role::with('permissions')->find($this->selectedScopeId);
        } else if ($this->selectedScopeName == 'User') {
            //$data['scope'] = User::with('team')->with('permissions')->findOrFail($id);
            $this->selectedScope  = User::with('permissions')->find($this->selectedScopeId);
        }
     }



    public function manageAccessControl() {

        $this->updateSelectedScope();
        if (!$this->selectedScope)
            return;

        $modelDiscovery = app(\QuickerFaster\UILibrary\Services\AccessControl\ModelDiscovery::class);
        $directory = $modelDiscovery->getModelsDirectory($this->selectedModule);
        $namespace = $modelDiscovery->getModelsNamespace($this->selectedModule);

        if ($directory && $namespace) {
            $this->resourceNames = ApplicationInfo::getAllModelNames($directory, $namespace);
        } else {
            $this->resourceNames = [];
        }


        AccessControlPermissionService::checkPermissionsExistsOrCreate($this->resourceNames);
        $this->setupResourceControlButtonGroup();


        $this->showResourceControlButtonGroup = true;
        $this->selectedModuleName = $this->selectedModule;
        $this->selectedModule = null;

    }



    private function setupResourceControlButtonGroup () {
        $this->resourceControlButtonGroup = [];


        foreach ($this->resourceNames as $resourceName) {
            $resourcePermissionNames = AccessControlPermissionService::getResourcePermissionNames($resourceName);
            if (empty($this->resourceControlButtonGroup[$resourceName]))
                $this->resourceControlButtonGroup[$resourceName] = $this->getPermissionConfig($resourceName, $resourcePermissionNames);
        }

    }




    private function getPermissionConfig($resource, $resourcePermissionNames) {
        $resourcePermissionNameConfig = [];


        foreach ($resourcePermissionNames as $key => $resourcePermissionName) {
            $control = explode('_',$resourcePermissionName)[0];
            $resource = explode('_',$resourcePermissionName)[1];

            //dd(boolval(in_array($resourcePermissionName, $this->selectedRole->getPermissionNames()->toArray())));

            $resourcePermissionNameConfig [] = [
                'model' => Role::class,
                'stateSyncMethod' => 'method',
                'recordId' => $this->selectedScopeId,
                'componentId' => $resourcePermissionName,
                'onStateValue' => $resourcePermissionName,
                'offStateValue' => '',
                'state' => boolval(in_array($resourcePermissionName, $this->selectedScope->getPermissionNames()->toArray())),
                'icon' => $this->controlsCSSClasses[$control]['icon'],
                'iconBg' => "light",
                'iconColor' => "dark",
                'subtitle' => "<span> <strong>".$this->selectedScope?->name."</strong> should be able to <strong>$control</strong> $resource records</span>",
            ];
        }

      return $resourcePermissionNameConfig;
    }








    public function render()
    {

        return view('qf::livewire.access-controls.access-control-manager');
    }
}
