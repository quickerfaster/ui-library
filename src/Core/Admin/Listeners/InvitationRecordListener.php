<?php

namespace QuickerFaster\UILibrary\Core\Admin\Listeners;

use QuickerFaster\UILibrary\Events\DataTableRecordSaved;
use QuickerFaster\UILibrary\Listeners\DataTableRecordListener;
use QuickerFaster\UILibrary\Mail\InvitationMail;
use QuickerFaster\UILibrary\Models\Invitation;
use QuickerFaster\UILibrary\Services\Invitations\InvitationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Listens for Invitation record creation via the DataTable form and
 * triggers token generation + email dispatch through InvitationService.
 *
 * This listener bridges the gap between the generic DataTable form
 * (which only creates the database record) and the invitation workflow
 * (which needs token generation and email delivery).
 */
class InvitationRecordListener extends DataTableRecordListener
{
    protected InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * When an Invitation record is created via the DataTable form,
     * generate a token, set expiration, and dispatch the email.
     */
    protected function handleCreated(DataTableRecordSaved $event): void
    {
        if ($event->model !== Invitation::class) {
            return;
        }

        $invitation = Invitation::find($event->newRecord['id'] ?? null);

        if (!$invitation) {
            return;
        }

        // Only process if the invitation is still pending and has no token
        // (i.e., it was just created via the form and hasn't been processed yet)
        if (!$invitation->isPending() || $invitation->token) {
            return;
        }

        // Complete the invitation: generate token, set expiration, send email
        $invitation->update([
            'token' => Str::random(64),
            'status' => Invitation::STATUS_PENDING,
            'expires_at' => now()->addDays(config('ui-library.invitations.expiration_days', 7)),
            'created_by' => auth()->id(),
        ]);

        // Dispatch the invitation email
        $acceptUrl = $this->invitationService->generateAcceptUrl($invitation);

        Mail::to($invitation->email)->queue(
            new InvitationMail($invitation, $acceptUrl)
        );
    }
}