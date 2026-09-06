<?php

namespace QuickerFaster\UILibrary\Listeners;

use QuickerFaster\UILibrary\Events\DataTableRecordSaved;

/**
 * Abstract base listener for DataTableRecordSaved events.
 *
 * Subclasses override the relevant hook methods to respond to record
 * lifecycle changes. Domain-specific filtering (which model/status to act on)
 * belongs in the subclass, not in this generic infrastructure.
 */
abstract class DataTableRecordListener
{
    /**
     * Handle the event by dispatching to the matching lifecycle hook.
     */
    public function handle(DataTableRecordSaved $event): void
    {
        match ($event->action) {
            DataTableRecordSaved::ACTION_CREATED => $this->handleCreated($event),
            DataTableRecordSaved::ACTION_UPDATED => $this->handleUpdated($event),
            DataTableRecordSaved::ACTION_DELETED => $this->handleDeleted($event),
            DataTableRecordSaved::ACTION_RESTORED => $this->handleRestored($event),
            default => null,
        };
    }

    /**
     * Hook called after a record is created.
     */
    protected function handleCreated(DataTableRecordSaved $event): void
    {
        //
    }

    /**
     * Hook called after a record is updated.
     */
    protected function handleUpdated(DataTableRecordSaved $event): void
    {
        //
    }

    /**
     * Hook called after a record is deleted.
     */
    protected function handleDeleted(DataTableRecordSaved $event): void
    {
        //
    }

    /**
     * Hook called after a soft-deleted record is restored.
     */
    protected function handleRestored(DataTableRecordSaved $event): void
    {
        //
    }

    /**
     * Check if the current user has one of the required roles.
     *
     * @param array<int, string> $requiredRoles
     */
    protected function isAuthorized(array $requiredRoles): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        foreach ($requiredRoles as $roleName) {
            if ($user->hasRole($roleName)) {
                return true;
            }
        }

        return false;
    }
}
