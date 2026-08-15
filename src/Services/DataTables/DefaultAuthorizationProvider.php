<?php

namespace QuickerFaster\UILibrary\Services\DataTables;

use Illuminate\Contracts\Auth\Authenticatable;
use QuickerFaster\UILibrary\Contracts\DataTables\DataTableAuthorizationProvider;
use QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService;

/**
 * Default authorization provider for DataTable operations.
 *
 * Uses Spatie Permission for authorization checks. The consuming application
 * can override this by binding a different implementation to the
 * DataTableAuthorizationProvider contract.
 *
 * Permission naming convention: {action}_{resource}
 *   - view_employee, create_employee, edit_employee, delete_employee
 */
class DefaultAuthorizationProvider implements DataTableAuthorizationProvider
{
    /**
     * Check if the user can access a given view.
     *
     * Super admins and admins bypass all granular permission checks
     * via AuthorizationService::isBypassAllowed().
     */
    public function canAccessView(Authenticatable $user, string $viewName): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        return $user->can('view_' . $viewName);
    }

    public function canView(Authenticatable $user, object $record): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewName($record);

        return $user->can('view_' . $viewName);
    }

    public function canCreate(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('create_' . $viewName);
    }

    public function canUpdate(Authenticatable $user, object $record): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewName($record);

        return $user->can('edit_' . $viewName);
    }

    public function canDelete(Authenticatable $user, object $record): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewName($record);

        return $user->can('delete_' . $viewName);
    }

    public function canRestore(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('restore_' . $viewName);
    }

    public function canForceDelete(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('force_delete_' . $viewName);
    }

    public function canPerformAction(Authenticatable $user, string $action, object $record): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewName($record);

        return $user->can($action . '_' . $viewName);
    }

    public function canExport(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('export_' . $viewName);
    }

    public function canImport(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('import_' . $viewName);
    }

    public function canPrint(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('print_' . $viewName);
    }

    public function canBulkDelete(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('delete_' . $viewName);
    }

    public function canBulkRestore(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('restore_' . $viewName);
    }

    public function canBulkForceDelete(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('force_delete_' . $viewName);
    }

    public function canBulkExport(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('export_' . $viewName);
    }

    public function canBulkUpdate(Authenticatable $user, string $modelClass): bool
    {
        if (AuthorizationService::isBypassAllowed($user)) {
            return true;
        }

        $viewName = $this->resolveViewNameFromClass($modelClass);

        return $user->can('edit_' . $viewName);
    }

    public function evaluateConditions(object $record, array $condition): bool
    {
        // Default implementation: if no conditions, allow.
        // Consuming apps should override with their own condition evaluator.
        if (empty($condition)) {
            return true;
        }

        // Support simple 'field' => 'value' equality checks
        foreach ($condition as $field => $expected) {
            if (!isset($record->$field) || $record->$field != $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve a kebab-case view name from a model instance.
     */
    protected function resolveViewName(object $record): string
    {
        return \Str::kebab(class_basename($record));
    }

    /**
     * Resolve a kebab-case view name from a model FQCN string.
     */
    protected function resolveViewNameFromClass(string $modelClass): string
    {
        return \Str::kebab(class_basename($modelClass));
    }
}
