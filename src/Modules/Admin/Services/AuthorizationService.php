<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthorizationService
{
    // Constants for view access control
    const ROLE_ADMIN_ONLY_VIEWS = [
        'user-role-management',
        'user-role-assignment',
        'access-control-management',
        'role-assignment'
    ];

    const CUSTOM_VIEW_MODEL_NAMES = [
        'user-role-management' => 'role',
        'assign-user-roles' => 'role',
        'access-control-management' => 'role',
        'employee-onboarding' => 'employee', // employee permission needed
        'payroll-wizard' => 'payroll_run', // payroll_run permission needed
    ];


    const ADMIN_ROLES = ['super_admin', 'company_admin'];

    /**
     * Check if user is super admin or company admin (bypass all granular checks).
     */
    private function isBypassAllowed($user): bool
    {
        return $user && $user->hasAnyRole(self::ADMIN_ROLES);
    }

    /**
     * Check if a user can access a given view (page).
     *
     * @param User|null $user
     * @param string $view
     * @return bool
     */
    public function canAccessView($user, string $view): bool
    {
        if (!$user) {
            return false;
        }

        // Bypass for super admin / company admin
        if ($this->isBypassAllowed($user)) {
            return true;
        }

        // Admin-only views (for regular admins, not bypassed)
        if (in_array($view, self::ROLE_ADMIN_ONLY_VIEWS)) {
            return $user->hasAnyRole(self::ADMIN_ROLES);
        }

        // Public views that don't require permission
        if (in_array($view, ['dashboard', 'my-profile'])) {
            return true;
        }

        // For non-admins, check specific view permission
        if (!$user->hasAnyRole(self::ADMIN_ROLES)) {
            $permission = 'view_' . $this->getViewModelName($view);
            return $user->can($permission);
        }

        return false;
    }

    /**
     * Convert a view name to the corresponding permission model name.
     */
    private function getViewModelName(string $view): string
    {
        if (array_key_exists($view, self::CUSTOM_VIEW_MODEL_NAMES)) {
            return self::CUSTOM_VIEW_MODEL_NAMES[$view];
        }

        /*if (str_contains($view, 'dashboard') || str_contains($view, 'overview')) {
            return 'overview';
        }*/

        if (str_starts_with($view, 'dashboard-')) {
            $view = str_replace("dashboard-", "", $view);
        }

        $view = str_replace('-', '_', $view);
        return Str::singular($view);
    }

    /**
     * Check if user can perform an action on a specific row (record).
     *
     * @param User $user
     * @param array $action  e.g. ['requiredPermission' => 'update_role']
     * @param Model $row
     * @return bool
     */
    public function canPerformAction(User $user, array $action, $row): bool
    {
        if ($this->isBypassAllowed($user)) {
            return true;
        }

        // 1. Check required role
        if (isset($action['requiredRole'])) {
            $requiredRoles = (array) $action['requiredRole'];
            if (!$user->hasAnyRole($requiredRoles)) {
                return false;
            }
        }

        // 2. Check required permission
        if (isset($action['requiredPermission'])) {
            $requiredPermissions = (array) $action['requiredPermission']; // This should be fixed with array of requiredPermissions
            if ($user->hasAnyPermission($requiredPermissions)) {
                return true;
            }
        }

        // 3. Check business conditions (state-based)
        if (isset($action['condition'])) { // This should be fixed with array of conditions
            $actions = (array) $action['condition'];
            if (!$this->checkBusinessConditions($row, $actions)) {
                return false;
            }
        }

        // 4. Check data scope (can user access this specific record's data?)
        if (!$this->isInUserScope($user, $row)) {
            return false;
        }

        return true;
    }



public function canBulkDelete($user, string $modelClass): bool
{
    if (!$user) return false;
    if ($this->isBypassAllowed($user)) return true;
    $modelName = $this->getModelNameFromClassName($modelClass);
    return $user->can('delete_' . $modelName);
}



public function canBulkRestore($user, string $modelClass): bool
{
    if (!$user) return false;
    if ($this->isBypassAllowed($user)) return true;
    $modelName = $this->getModelNameFromClassName($modelClass);
    return $user->can('restore_' . $modelName);
}


public function canBulkForceDelete($user, string $modelClass): bool
{
    if (!$user) return false;
    if ($this->isBypassAllowed($user)) return true;
    $modelName = $this->getModelNameFromClassName($modelClass);
    return $user->can('force_delete_' . $modelName);
}



public function canBulkExport($user, string $modelClass): bool
{
    if (!$user) return false;
    if ($this->isBypassAllowed($user)) return true;
    $modelName = $this->getModelNameFromClassName($modelClass);
    return $user->can('export_' . $modelName);
}


public function canBulkUpdate($user, string $modelClass): bool
{
    if (!$user) return false;
    if ($this->isBypassAllowed($user)) return true;
    $modelName = $this->getModelNameFromClassName($modelClass);
    return $user->can('edit_' . $modelName);
}



    /**
     * Generic authorization for any action on a record (or for creation).
     *
     * @param User|null $user
     * @param string $action  e.g., 'view', 'update', 'delete', 'create'
     * @param mixed $recordOrId  Model instance, or ID when $modelClass provided
     * @param string|null $modelClass  Required if $recordOrId is an ID (and for 'create')
     * @throws HttpException
     */
    public function authorize($user, string $action, $recordOrId = null, ?string $modelClass = null): void
    {
        if (!$user) {
            abort(403, 'Unauthenticated.');
        }

        // Handle 'create' action (no record needed)
        if ($action === 'create') {
            if (!$modelClass) {
                throw new \InvalidArgumentException('Model class is required for create authorization.');
            }
            if (!$this->canCreate($user, $modelClass)) {
                abort(403, "Unauthorized to create a {$this->getModelNameFromClassName($modelClass)}.");
            }
            return;
        }

        // For other actions, resolve the record
        $record = $this->resolveRecord($recordOrId, $modelClass);
        $can = match($action) {
            'view' => $this->canView($user, $record),
            'edit', 'update' => $this->canUpdate($user, $record),
            'delete' => $this->canDelete($user, $record),
            default => false,
        };

        if (!$can) {
            $modelName = $this->getModelNameFromRecord($record);
            abort(403, "Unauthorized to {$action} this {$modelName}.");
        }
    }

    /**
     * Convenience method for view authorization.
     */
    public function authorizeView($user, $recordOrId, ?string $modelClass = null): void
    {
        $this->authorize($user, 'view', $recordOrId, $modelClass);
    }

    /**
     * Convenience method for update authorization.
     */
    public function authorizeUpdate($user, $recordOrId, ?string $modelClass = null): void
    {
        $this->authorize($user, 'edit', $recordOrId, $modelClass);
    }

    public function authorizeEdit($user, $recordOrId, ?string $modelClass = null): void
    {
        $this->authorize($user, 'edit', $recordOrId, $modelClass);
    }

    /**
     * Convenience method for create authorization.
     */
    public function authorizeCreate($user, string $modelClass): void
    {
        $this->authorize($user, 'create', null, $modelClass);
    }

    /**
     * Convenience method for delete authorization.
     */
    public function authorizeDelete($user, $recordOrId, ?string $modelClass = null): void
    {
        $this->authorize($user, 'delete', $recordOrId, $modelClass);
    }

    // -------------------------------------------------------------------------
    // Boolean "can" methods (non‑aborting) – all respect bypass
    // -------------------------------------------------------------------------

    /**
     * Check if user can view a record.
     */
    public function canView($user, $record): bool
    {
        if (!$user) return false;
        if ($this->isBypassAllowed($user)) return true;
        $action = ['requiredPermission' => 'view_' . $this->getModelNameFromRecord($record)];
        return $this->canPerformAction($user, $action, $record);
    }

    /**
     * Check if user can update a record.
     */
    public function canUpdate($user, $record): bool
    {
        // Note that "update" is represented by "edit"
        if (!$user) return false;
        if ($this->isBypassAllowed($user)) return true;
        $action = ['requiredPermission' => 'edit_' . $this->getModelNameFromRecord($record)];
        return $this->canPerformAction($user, $action, $record);
    }

    /**
     * Check if user can delete a record.
     */
    public function canDelete($user, $record): bool
    {
        if (!$user) return false;
        if ($this->isBypassAllowed($user)) return true;
        $action = ['requiredPermission' => 'delete_' . $this->getModelNameFromRecord($record)];
        return $this->canPerformAction($user, $action, $record);
    }

    /**
     * Check if user can create a new record.
     */
    public function canCreate($user, string $modelClass): bool
    {
        if (!$user) return false;
        if ($this->isBypassAllowed($user)) return true;
        $modelName = $this->getModelNameFromClassName($modelClass);
        return $user->can('create_' . $modelName);
    }

    /**
     * Check if user can export records.
     */
    public function canExport($user, string $modelClass): bool
    {
        if (!$user) return false;
        if ($this->isBypassAllowed($user)) return true;
        $modelName = $this->getModelNameFromClassName($modelClass);
        return $user->can('export_' . $modelName);
    }

    /**
     * Check if user can import records.
     */
    public function canImport($user, string $modelClass): bool
    {
        if (!$user) return false;
        if ($this->isBypassAllowed($user)) return true;
        $modelName = $this->getModelNameFromClassName($modelClass);
        return $user->can('import_' . $modelName);
    }

    /**
     * Check if user can print.
     */
    public function canPrint($user, string $modelClass): bool
    {
        if (!$user) return false;
        if ($this->isBypassAllowed($user)) return true;
        $modelName = $this->getModelNameFromClassName($modelClass);
        return $user->can('print_' . $modelName);
    }

    /**
     * Check if user can restore a soft‑deleted record.
     */
    public function canRestore($user, string $modelClass): bool
    {
        if (!$user) return false;
        if ($this->isBypassAllowed($user)) return true;
        $modelName = $this->getModelNameFromClassName($modelClass);
        return $user->can('restore_' . $modelName);
    }

    /**
     * Check if user can force delete (permanently delete) a record.
     */
    public function canForceDelete($user, string $modelClass): bool
    {
        if (!$user) return false;
        if ($this->isBypassAllowed($user)) return true;
        $modelName = $this->getModelNameFromClassName($modelClass);
        return $user->can('forceDelete_' . $modelName);
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a record from either a model instance or an ID + class name.
     */
    private function resolveRecord($recordOrId, ?string $modelClass = null): Model
    {
        if ($recordOrId instanceof Model) {
            return $recordOrId;
        }
        if (is_int($recordOrId) && $modelClass && class_exists($modelClass)) {
            return $modelClass::findOrFail($recordOrId);
        }
        throw new \InvalidArgumentException('Invalid record or ID/class combination.');
    }

    /**
     * Get the model name (snake_case) from a record instance.
     */
    private function getModelNameFromRecord(Model $record): string
    {
        return Str::snake(class_basename($record));
    }

    /**
     * Get the model name from a fully qualified class name.
     */
    private function getModelNameFromClassName(string $className): string
    {
        return Str::snake(class_basename($className));
    }

    /**
     * Check business/state conditions on a row.
     */
    private function checkBusinessConditions($row, array $conditions): bool
    {
        foreach ($conditions as $field => $expectedValue) {
            $actualValue = data_get($row, $field);
            if ($actualValue != $expectedValue) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if a row is within the user's data scope.
     */
    private function isInUserScope(User $user, $row): bool
    {
        $employeeId = $row->employee_id ?? $row->id;

        if ($user->hasRole('employee')) {
            return $user->employee_id == $employeeId;
        }

        if ($user->hasRole('manager')) {
            $managedEmployeeIds = $user->managedEmployees()->pluck('id')->toArray();
            return in_array($employeeId, $managedEmployeeIds);
        }

        // company_admin and super_admin already bypassed earlier, but if not bypassed,
        // they are allowed full scope.
        if ($user->hasAnyRole(\App\Modules\Admin\Services\AuthorizationService::ADMIN_ROLES)) {
            return true;
        }

        return false;
    }
}
