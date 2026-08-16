<?php

namespace QuickerFaster\UILibrary\Http\Livewire\AccessControls;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use QuickerFaster\UILibrary\Services\PermissionSyncService;

class PermissionManager extends Component
{
    public string $scopeType = 'Role';
    public ?int $scopeId = null;
    public $scope;
    public array $permissions = []; // flat [permissionName => bool]

    
    public array $resourceNames = [];
    public array $controls = ['view', 'create', 'edit', 'delete', 'print', 'export', 'import'];
    public array $controlsCSSClasses = []; // as in your original
    public bool $showSuccess = false;

protected $listeners = ['groupPermissionsChanged' => 'updateGroupPermissions'];

    public function mount()
    {
        $this->controlsCSSClasses = $this->getControlsCSSClasses();
        $this->resourceNames = $this->getResourceNames(); // your dynamic method
    }





public function updateGroupPermissions($groupId, $newPermissions)
{
    foreach ($newPermissions as $perm => $value) {
        if (array_key_exists($perm, $this->permissions)) {
            $this->permissions[$perm] = $value;
        }
    }
}






    public function updatedScopeId($id)
    {
        $this->loadScopeAndPermissions();
    }

    protected function loadScopeAndPermissions()
    {
        if (!$this->scopeId) return;
        $modelClass = $this->scopeType === 'Role' ? Role::class : User::class;
        $this->scope = $modelClass::with('permissions')->find($this->scopeId);
        if (!$this->scope) return;

        $currentPermissions = $this->scope->getPermissionNames()->toArray();
        $allPermissions = $this->generateAllPermissions();
        foreach ($allPermissions as $perm) {
            $this->permissions[$perm] = in_array($perm, $currentPermissions);
        }
    }

    protected function generateAllPermissions(): array
    {
        $perms = [];
        foreach ($this->resourceNames as $resource) {
            $resourceSnake = Str::snake($resource);
            foreach ($this->controls as $control) {
                $perms[] = $control . '_' . $resourceSnake;
            }
        }
        return $perms;
    }

    public function updatePermission($permissionName, $value)
    {
        if (array_key_exists($permissionName, $this->permissions)) {
            $this->permissions[$permissionName] = $value;
        }
    }

    public function saveAllPermissions()
    {
        if (!$this->scope) return;
        $granted = array_keys(array_filter($this->permissions));
        $this->scope->syncPermissions($granted);
        $this->showSuccess = true;
    }

    public function getGroupedPermissionsProperty()
    {
        $groups = [];
        foreach ($this->resourceNames as $resource) {
            $resourceSnake = Str::snake($resource);
            $groups[$resource] = [];
            foreach ($this->controls as $control) {
                $perm = $control . '_' . $resourceSnake;
                if (isset($this->permissions[$perm])) {
                    $groups[$resource][$perm] = $this->permissions[$perm];
                }
            }
        }
        return $groups;
    }



public function getScopesProperty()
{
    $modelClass = $this->scopeType === 'Role' ? Role::class : User::class;
    return $modelClass::pluck('name', 'id');
}




    public function render()
    {
        return view('qf::livewire.access-controls.permission-manager', [
            'groupedPermissions' => $this->grouped_permissions,
        ]);
    }

    private function getControlsCSSClasses(): array
    {
        return [
            'view'   => ['icon' => 'fas fa-eye', 'bg' => 'info', 'color' => 'white'],
            'create' => ['icon' => 'fas fa-plus', 'bg' => 'success', 'color' => 'white'],
            'edit'   => ['icon' => 'fas fa-edit', 'bg' => 'warning', 'color' => 'dark'],
            'delete' => ['icon' => 'fas fa-trash', 'bg' => 'danger', 'color' => 'white'],
            'print'  => ['icon' => 'fas fa-print', 'bg' => 'secondary', 'color' => 'white'],
            'export' => ['icon' => 'fas fa-download', 'bg' => 'primary', 'color' => 'white'],
            'import' => ['icon' => 'fas fa-upload', 'bg' => 'primary', 'color' => 'white'],
        ];
    }

    private function getResourceNames(): array
    {
        // Use your existing logic (scanning modules)
        return ['Record', 'Store', 'Inventory']; // example
    }
}