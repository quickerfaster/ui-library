<?php

namespace QuickerFaster\UILibrary\Contracts\DataTables;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Contract for DataTable authorization checks.
 *
 * The consuming application can bind its own implementation to this contract
 * to provide custom authorization logic for DataTable operations.
 *
 * Default implementation: {@see \QuickerFaster\UILibrary\Services\DataTables\DefaultAuthorizationProvider}
 */
interface DataTableAuthorizationProvider
{
    /**
     * Check if the given user can access (view) the specified resource.
     */
    public function canAccessView(Authenticatable $user, string $viewName): bool;

    /**
     * Check if the given user can view a specific record.
     */
    public function canView(Authenticatable $user, object $record): bool;

    /**
     * Check if the given user can create records for the specified resource.
     */
    public function canCreate(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can update the specified model record.
     */
    public function canUpdate(Authenticatable $user, object $record): bool;

    /**
     * Check if the given user can delete the specified model record.
     */
    public function canDelete(Authenticatable $user, object $record): bool;

    /**
     * Check if the given user can restore soft-deleted records.
     */
    public function canRestore(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can force-delete records.
     */
    public function canForceDelete(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can perform a specific action on a record.
     */
    public function canPerformAction(Authenticatable $user, string $action, object $record): bool;

    /**
     * Check if the given user can export records.
     */
    public function canExport(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can import records.
     */
    public function canImport(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can print records.
     */
    public function canPrint(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can bulk-delete records.
     */
    public function canBulkDelete(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can bulk-restore records.
     */
    public function canBulkRestore(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can bulk force-delete records.
     */
    public function canBulkForceDelete(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can bulk-export records.
     */
    public function canBulkExport(Authenticatable $user, string $modelClass): bool;

    /**
     * Check if the given user can bulk-update records.
     */
    public function canBulkUpdate(Authenticatable $user, string $modelClass): bool;

    /**
     * Evaluate business conditions for a row action.
     * Returns true if the conditions are met for the given record.
     *
     * @param object $record
     * @param array $condition  Condition definition from Data config
     * @return bool
     */
    public function evaluateConditions(object $record, array $condition): bool;
}