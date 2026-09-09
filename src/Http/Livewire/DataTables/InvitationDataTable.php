<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use QuickerFaster\UILibrary\Models\Invitation;
use QuickerFaster\UILibrary\Services\Invitations\InvitationService;

/**
 * Custom DataTable for Invitations.
 *
 * Extends the base DataTable to handle invitation-specific moreActions:
 * resend, revoke, and copy invitation link.
 */
class InvitationDataTable extends DataTable
{
    /**
     * Additional listeners for invitation-specific events.
     *
     * @var array
     */
    protected $listeners = [
        'performDelete' => 'performDelete',
        'refreshDataTable' => '$refresh',
        'executeBulkAction' => 'executeBulkAction',
        'filtersUpdated' => 'updateFilters',
        'executeRowAction' => 'executeRowAction',
        'searchApplied' => 'applySearchPanel',
        'columnsUpdated' => 'handleColumnsUpdated',
        'resendInvitation' => 'resendInvitation',
        'revokeInvitation' => 'revokeInvitation',
        'copyInvitationLink' => 'copyInvitationLink',
    ];

    /**
     * Override executeRowAction to handle the 'event' key in moreActions.
     *
     * The base DataTable does not handle a plain 'event' key; it only
     * handles 'dispatchLivewireEvent' (with eventName/params). This
     * override dispatches the 'event' value as a Livewire event so that
     * listeners (resendInvitation, revokeInvitation, copyInvitationLink)
     * receive it.
     *
     * @param  array  $params
     * @return void
     */
    public function executeRowAction($params): void
    {
        if (empty($params) || !is_array($params)) {
            return;
        }

        if (!isset($params['actionIndex']) || !isset($params['recordId'])) {
            return;
        }

        $actionIndex = $params['actionIndex'];
        $recordId = $params['recordId'];

        $action = $this->moreActions[$actionIndex] ?? null;
        if (!$action) {
            return;
        }

        // If the action defines a plain 'event' key, dispatch it as a
        // Livewire event and let the dedicated listener handle the rest.
        if (!empty($action['event'])) {
            $this->dispatch($action['event'], $recordId);
            return;
        }

        // Fall through to the parent implementation for all other action types.
        parent::executeRowAction($params);
    }

    /**
     * Resend an invitation.
     *
     * @param  int|string  $recordId
     * @return void
     */
    public function resendInvitation($recordId): void
    {
        $invitation = Invitation::find($recordId);

        if (!$invitation) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Invitation not found.',
            ]);
            return;
        }

        /** @var InvitationService $invitationService */
        $invitationService = app(InvitationService::class);
        $invitationService->resend($invitation);

        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => 'Invitation resent successfully to ' . $invitation->email . '.',
            'autoClose' => true,
        ]);

        $this->dispatch('$refresh');
    }

    /**
     * Revoke an invitation.
     *
     * @param  int|string  $recordId
     * @return void
     */
    public function revokeInvitation($recordId): void
    {
        $invitation = Invitation::find($recordId);

        if (!$invitation) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Invitation not found.',
            ]);
            return;
        }

        /** @var InvitationService $invitationService */
        $invitationService = app(InvitationService::class);
        $invitationService->revoke($invitation);

        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => 'Invitation for ' . $invitation->email . ' has been revoked.',
            'autoClose' => true,
        ]);

        $this->dispatch('$refresh');
    }

    /**
     * Copy the invitation accept link to the clipboard.
     *
     * Generates the signed accept URL and dispatches a browser event
     * so Alpine.js can write it to the clipboard via navigator.clipboard.
     *
     * @param  int|string  $recordId
     * @return void
     */
    public function copyInvitationLink($recordId): void
    {
        $invitation = Invitation::find($recordId);

        if (!$invitation) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Invitation not found.',
            ]);
            return;
        }

        /** @var InvitationService $invitationService */
        $invitationService = app(InvitationService::class);
        $acceptUrl = $invitationService->generateAcceptUrl($invitation);

        // Dispatch a browser event for Alpine.js to copy to clipboard.
        $this->dispatch('copyToClipboard', [
            'text' => $acceptUrl,
            'message' => 'Invitation link copied to clipboard!',
        ]);
    }
}