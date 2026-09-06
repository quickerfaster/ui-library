<?php

namespace QuickerFaster\UILibrary\Http\Livewire\AccessControls;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
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

    public $modelSearch = '';


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

    /**
     * Incremented whenever permission state changes so nested Livewire
     * components are re-keyed (and thus re-mounted) to reflect new state.
     */
    public $controlButtonGroupVersion = 0;




    protected $listeners = [
    ];


    public function mount($selectedModule = null, $isUrlAccess = false) {

        $this->selectedModule = $selectedModule;
        $this->isUrlAccess = $isUrlAccess;

        // Modules filtering
        $moduleConfig = config('ui-library.access_control.modules', []);
        $this->moduleNames = $this->getFilteredModules($moduleConfig);

        $selectedScopeClassName = match($this->selectedScopeName) {
            'Role' => \Spatie\Permission\Models\Role::class,
            'User' => \App\Models\User::class,
            default => "App\\Models\\".$this->selectedScopeName,
        };

        if (strtolower($this->selectedScopeName) == "role") {
            // Roles filtering
            $roleConfig = config('ui-library.access_control.roles', []);
            $this->scopeNames = $this->getFilteredRoles($roleConfig);
        } else {
            $this->scopeNames = $selectedScopeClassName::all()->pluck("name", "id");
        }
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

        // Models filtering
        $modelConfig = config('ui-library.access_control.models', []);
        $this->resourceNames = $this->getFilteredModels($modelConfig, $this->resourceNames);

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






    /**
     * Filter the discovered resource (model) names by the current model search
     * term. The query is tokenized into words and every word must match
     * (AND semantics) against a rich searchable representation of each model.
     *
     * @return array<int, string>
     */
    public function getFilteredResourceNamesProperty(): array
    {
        $query = trim((string) $this->modelSearch);

        if ($query === '') {
            return $this->resourceNames;
        }

        $words = preg_split('/\s+/', strtolower($query)) ?: [];

        return array_values(array_filter($this->resourceNames, function ($resourceName) use ($words) {
            $haystack = $this->buildResourceSearchText((string) $resourceName);

            foreach ($words as $word) {
                if ($word === '' || !str_contains($haystack, $word)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Build a rich, lowercase searchable string for a model resource so that
     * partial words, camelCase splits, snake_case and display labels all match.
     *
     * @param  string  $resourceName
     * @return string
     */
    protected function buildResourceSearchText(string $resourceName): string
    {
        $basename = class_basename($resourceName);
        $headline = Str::headline($basename);      // "Business Unit"
        $snake = Str::snake($basename);            // "business_unit"
        $kebab = Str::kebab($basename);            // "business-unit"
        $plural = Str::plural($basename);          // "BusinessUnits"

        $parts = [
            $basename,
            $headline,
            $snake,
            $kebab,
            $plural,
            Str::headline($plural),                // "Business Units"
            $headline . ' management',             // "Business Unit Management"
        ];

        // Permission action labels, e.g. "view business unit", "view_business_unit".
        foreach ($this->controlList as $action) {
            $parts[] = strtolower($action) . ' ' . strtolower($headline);
            $parts[] = strtolower($action) . '_' . $snake;
        }

        return strtolower(implode(' ', $parts));
    }

    /**
     * Bulk toggle a single permission action (view, create, edit, delete,
     * print, export, import) across every model in the selected module.
     */
    public function bulkToggle(string $action, bool $value): void
    {
        
        if (!in_array($action, $this->controlList, true)) {
            return;
        }

        $this->updateSelectedScope();

        if (!$this->selectedScope) {
            return;
        }

        $permissions = $this->selectedScope->getPermissionNames()->toArray();

        foreach ($this->resourceNames as $resourceName) {
            $permissionName = strtolower($action . '_' . Str::snake(class_basename((string) $resourceName)));

            if ($value) {
                $permissions = array_unique(array_merge([$permissionName], $permissions));
            } else {
                $permissions = array_values(array_diff($permissions, [$permissionName]));
            }
        }

        $this->selectedScope->syncPermissions($permissions);

        $this->refreshPermissions();

        $this->dispatch('refresh-toggle-state', permissions: $this->selectedScope->getPermissionNames()->toArray());
    }

    /**
     * Reload the selected scope and rebuild permission card states so the UI
     * reflects the latest granted permissions after a bulk toggle.
     */
    public function refreshPermissions(): void
    {
        $this->updateSelectedScope();

        if ($this->selectedScope) {
            $this->setupResourceControlButtonGroup();
        }

        // Force nested Livewire components to re-mount with fresh state.
        $this->controlButtonGroupVersion++;
    }

    /**
     * Determine the bulk toggle state for each permission action across every
     * model in the selected module.
     *
     * Returns 'on' when every model has the action granted, 'off' when none do,
     * and 'mixed' when only some do. Permission names are built with the same
     * format used by bulkToggle() so the switches stay in sync.
     *
     * @return array<string, string>
     */
    public function getBulkToggleStatesProperty(): array
    {
        $states = [];

        foreach ($this->controlList as $control) {
            $states[$control] = 'off';
        }

        if (!$this->selectedScope || empty($this->resourceNames)) {
            return $states;
        }

        $granted = $this->selectedScope->getPermissionNames()->toArray();

        foreach ($this->controlList as $control) {
            $total = 0;
            $grantedCount = 0;

            foreach ($this->resourceNames as $resourceName) {
                $permissionName = strtolower($control . '_' . Str::snake(class_basename((string) $resourceName)));
                $total++;

                if (in_array($permissionName, $granted, true)) {
                    $grantedCount++;
                }
            }

            if ($total > 0 && $grantedCount === $total) {
                $states[$control] = 'on';
            } elseif ($total > 0 && $grantedCount > 0) {
                $states[$control] = 'mixed';
            }
        }

        return $states;
    }

    public function render()
    {

        return view('qf::livewire.access-controls.access-control-manager');
    }

    /**
     * Resolve the roles shown in the permission assignment dropdown.
     *
     * Preserves the original behaviour of hiding the admin bypass roles,
     * then applies the configured include/exclude filters.
     */
    protected function getFilteredRoles(array $config): Collection
    {
        $roles = \Spatie\Permission\Models\Role::all()->pluck('name', 'id');

        // Admin roles bypass granular permissions, so they are never assignable.
        $roles = $roles->reject(
            fn ($name) => in_array($name, AuthorizationService::ADMIN_ROLES_ARRAY, true)
        );

        return $this->applyIncludeExclude($roles, $config);
    }

    /**
     * Resolve the modules shown in the permission assignment dropdown.
     */
    protected function getFilteredModules(array $config): array
    {
        $modules = collect(ApplicationInfo::getModuleNames());

        return $this->applyIncludeExclude(
            $modules,
            $config,
            fn ($module) => strtolower((string) $module)
        )->values()->all();
    }

    /**
     * Resolve the models shown as permission cards.
     *
     * Accepts both bare model names ('User') and FQCNs ('App\Models\User')
     * in the include/exclude lists.
     */
    protected function getFilteredModels(array $config, array $models): array
    {
        $items = collect($models);

        return $this->applyIncludeExclude(
            $items,
            $config,
            fn ($model) => class_basename((string) $model)
        )->values()->all();
    }

    /**
     * Apply shared include/exclude filtering to a collection of values.
     *
     * - include === '*' keeps everything
     * - include is an array keeps only matching values
     * - exclude is an array always removes matching values
     *
     * The optional normalizer maps both collection values and config entries
     * to a common comparison key (e.g. lowercase module names).
     */
    protected function applyIncludeExclude(Collection $items, array $config, ?callable $normalizer = null): Collection
    {
        $normalizer = $normalizer ?? fn ($value) => $value;

        $include = $config['include'] ?? '*';
        $exclude = $config['exclude'] ?? [];

        if ($include !== '*' && is_array($include)) {
            $include = array_map($normalizer, $include);
            $items = $items->filter(fn ($value) => in_array($normalizer($value), $include, true));
        }

        if (!empty($exclude) && is_array($exclude)) {
            $exclude = array_map($normalizer, $exclude);
            $items = $items->reject(fn ($value) => in_array($normalizer($value), $exclude, true));
        }

        return $items;
    }
}
