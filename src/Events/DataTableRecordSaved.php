<?php

namespace QuickerFaster\UILibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Livewire\Component;

/**
 * Fired whenever a DataTable record is created, updated, deleted, or restored.
 *
 * This is the generic extension point every business module needs to hook into
 * DataTable form saves and record lifecycle changes. The payload intentionally
 * carries only domain-agnostic state: the previous record, the new record, the
 * model FQCN, and the action that occurred.
 */
class DataTableRecordSaved
{
    use Dispatchable, SerializesModels;

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_RESTORED = 'restored';

    /**
     * @param array|null $oldRecord The record state before the change.
     * @param array|null $newRecord The record state after the change.
     * @param string $model         Fully-qualified model class name.
     * @param string $action        One of the ACTION_* constants.
     * @param Component|null $component The Livewire component that dispatched
     *                              the event, enabling listeners to provide
     *                              browser feedback (e.g. SweetAlert).
     */
    public function __construct(
        public ?array $oldRecord,
        public ?array $newRecord,
        public string $model,
        public string $action,
        public ?Component $component = null,
    ) {}
}
