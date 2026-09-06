<?php

namespace QuickerFaster\UILibrary\Services\AccessControl;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class AccessControlPermissionService
{

    const MSG_PERMISSION_DENIED = "You do not have permission to perform this action.";

    const PERMISSION_ACTIONS = ['view', 'create', 'edit', 'delete', 'print', 'export', 'import'];

    const DEFAULT_ACTIONS = ['view', 'create', 'edit', 'delete'];


    public static function checkPermission($action, $modelName): bool
    {

        // If not logged in return false
        if (!auth()->check()) {
            return false;
        }

        $permissionName = $action . "_" . Str::snake($modelName);
        return auth()->user()->hasPermissionTo($permissionName);
    }

    /**
     * Create permissions for a list of resource names using firstOrCreate
     * for idempotent seeding.
     *
     * @param array<int, string> $resourceNames
     * @param array<int, string>|null $actions  Override default action set; null uses PERMISSION_ACTIONS
     */
    public static function checkPermissionsExistsOrCreate(array $resourceNames, ?array $actions = null): void
    {
        $actions = $actions ?? self::PERMISSION_ACTIONS;

        foreach ($resourceNames as $resourceName) {
            $permissionNames = self::getResourcePermissionNames($resourceName, $actions);
            foreach ($permissionNames as $permissionName) {
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['name' => $permissionName, 'guard_name' => 'web']
                );
            }
        }
    }

    /**
     * Get resource permission name list.
     *
     * @param array<int, string>|null $actions
     * @return array<int, string>
     */
    public static function getResourcePermissionNames(string $resourceName, ?array $actions = null): array
    {
        $actions = $actions ?? self::PERMISSION_ACTIONS;
        $resourcePermissionNames = [];
        $resourceName = Str::snake($resourceName);
        foreach ($actions as $control) {
            $resourcePermissionNames[] = strtolower($control . "_" . $resourceName);
        }
        return $resourcePermissionNames;
    }

    /**
     * Seed permission names for all models discovered across business modules
     * and core modules, respecting per-module permissions.php config files.
     *
     * Convention: app/Modules/{Module}/Config/permissions.php
     *
     * Each file may return an array with optional keys:
     *   - 'only'    => string[]  Whitelist specific model class names
     *   - 'except'  => string[]  Blacklist specific model class names
     *   - 'extra'   => string[]  Additional permission names beyond CRUD defaults
     *   - 'actions' => string[]  Override the default action set (e.g. ['view','create','export'])
     *
     * Opt-out: config('ui-library.modules.{module}.auto_register_permissions')
     *
     * Idempotent: uses firstOrCreate.
     */
    public static function seedPermissionNames(): void
    {
        $modelDiscovery = app(ModelDiscovery::class);

        // Get all module names
        $moduleNames = $modelDiscovery->getModuleNames();

        foreach ($moduleNames as $moduleName) {
            // Check opt-out
            $moduleKey = strtolower($moduleName);
            if (!config("ui-library.modules.{$moduleKey}.auto_register_permissions", true)) {
                continue;
            }

            // Read per-module permissions.php config
            $config = self::getModulePermissionConfig($moduleName);

            // Determine which models to include
            $modelNames = $modelDiscovery->getModelNames($moduleName);

            if (isset($config['only'])) {
                $modelNames = array_intersect($modelNames, (array) $config['only']);
            }

            if (isset($config['except'])) {
                $modelNames = array_diff($modelNames, (array) $config['except']);
            }

            // Determine which actions to use
            $actions = $config['actions'] ?? self::PERMISSION_ACTIONS;

            // Seed model-based permissions
            if (!empty($modelNames)) {
                self::checkPermissionsExistsOrCreate($modelNames, $actions);
            }

            // Seed extra permissions
            if (!empty($config['extra'])) {
                foreach ((array) $config['extra'] as $extraPermission) {
                    Permission::firstOrCreate(
                        ['name' => $extraPermission, 'guard_name' => 'web'],
                        ['name' => $extraPermission, 'guard_name' => 'web']
                    );
                }
            }
        }
    }

    /**
     * Read the per-module permissions.php config file.
     *
     * @param string $moduleName  e.g. 'Admin', 'Hr'
     * @return array{only?: string[], except?: string[], extra?: string[], actions?: string[]}
     */
    public static function getModulePermissionConfig(string $moduleName): array
    {
        $businessPath = config('ui-library.module_paths.business', app_path('Modules'));
        $configPath = $businessPath . '/' . ucfirst($moduleName) . '/Config/permissions.php';

        if (File::exists($configPath)) {
            $config = require $configPath;
            return is_array($config) ? $config : [];
        }

        return [];
    }

    /**
     * Get all discoverable permission names grouped by module, for the
     * ui-library:discover command. Respects opt-outs and per-module config.
     *
     * @return array<string, array{models: array<string, string[]>, extra: string[]}>
     */
    public static function getDiscoverablePermissionNames(): array
    {
        $modelDiscovery = app(ModelDiscovery::class);
        $moduleNames = $modelDiscovery->getModuleNames();
        $result = [];

        foreach ($moduleNames as $moduleName) {
            $moduleKey = strtolower($moduleName);

            if (!config("ui-library.modules.{$moduleKey}.auto_register_permissions", true)) {
                continue;
            }

            $config = self::getModulePermissionConfig($moduleName);
            $modelNames = $modelDiscovery->getModelNames($moduleName);

            if (isset($config['only'])) {
                $modelNames = array_intersect($modelNames, (array) $config['only']);
            }

            if (isset($config['except'])) {
                $modelNames = array_diff($modelNames, (array) $config['except']);
            }

            $actions = $config['actions'] ?? self::PERMISSION_ACTIONS;

            $moduleResult = [
                'models' => [],
                'extra' => $config['extra'] ?? [],
            ];

            foreach ($modelNames as $modelName) {
                $moduleResult['models'][$modelName] = self::getResourcePermissionNames($modelName, $actions);
            }

            if (!empty($moduleResult['models']) || !empty($moduleResult['extra'])) {
                $result[$moduleKey] = $moduleResult;
            }
        }

        return $result;
    }

    public static function isOwner($model, $id)
    {
        // Query the model record
        $model = $model::find($id);

        // Check if the model is a user and the user is accessing his profile
        if ($model instanceof User) {
            // Extract the last url part which is view or id
            $url = url()->previous(); // OR request()->headers->get('referer')
            $segments = explode("/", $url);
            $view = end($segments);
            if ($view == "my-profile")
                return $model->id == $id;
        }

        // Check other possible resource ownership 
        $user = auth()->user();
        if ($model->user_id == $user->id) {
            return true;
        } else if ($model->team_id == $user->current_team_id) {
            return true;
        } else if ($model->created_by == $user->id) {
            return true;
        } else if ($model->updated_by == $user->id) {
            return true;
        }
        return false;

    }

}
