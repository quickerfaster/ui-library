<?php

namespace QuickerFaster\UILibrary\Services\Invitations;

use QuickerFaster\UILibrary\Models\Invitation;
use QuickerFaster\UILibrary\Mail\InvitationMail;
use QuickerFaster\UILibrary\Events\Invitations\InvitationSent;
use QuickerFaster\UILibrary\Events\Invitations\InvitationAccepted;
use QuickerFaster\UILibrary\Events\Invitations\InvitationExpired;
use QuickerFaster\UILibrary\Events\Invitations\InvitationRevoked;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;

class InvitationService
{
    /**
     * Create a new invitation and dispatch the email.
     */
    public function create(string $email, string $role, ?string $message = null, $invitable = null, ?int $createdBy = null): Invitation
    {
        $invitation = Invitation::create([
            'email' => $email,
            'token' => Str::random(64),
            'status' => Invitation::STATUS_PENDING,
            'role' => $role,
            'invitable_type' => $invitable ? $invitable->getInvitableType() : null,
            'invitable_id' => $invitable ? $invitable->getInvitableId() : null,
            'message' => $message,
            'expires_at' => now()->addDays(config('ui-library.invitations.expiration_days', 7)),
            'created_by' => $createdBy,
        ]);

        $this->sendMail($invitation);

        InvitationSent::dispatch($invitation);

        return $invitation;
    }

    /**
     * Accept an invitation: validate token, create/activate user, mark accepted.
     */
    public function accept(string $token, string $password, ?string $name = null): ?Invitation
    {
        $invitation = $this->findValidByToken($token);

        if (! $invitation) {
            return null;
        }

        // If no name provided, derive from email
        if (empty($name)) {
            $name = explode('@', $invitation->email)[0];
        }

        $userModel = config('auth.providers.users.model');

        // Create or update user
        $user = $userModel::firstOrNew(['email' => $invitation->email]);
        $user->password = Hash::make($password);
        $user->name = $name;

        $user->status = 'active';
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();

        // Assign role if Spatie permissions is available
        if (method_exists($user, 'assignRole') && $invitation->role) {
            // Resolve role name from ID before assigning
            $roleName = $invitation->role;
            if (is_numeric($roleName)) {
                $role = \Spatie\Permission\Models\Role::find($roleName);
                $roleName = $role ? $role->name : null;
            }

            if ($roleName) {
                $user->assignRole($roleName);
            }
        }

        $invitation->update([
            'status' => Invitation::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        InvitationAccepted::dispatch($invitation);

        return $invitation;
    }

    /**
     * Resend an invitation email and reset expiration.
     */
    public function resend(Invitation $invitation): void
    {
        $invitation->update([
            'expires_at' => now()->addDays(config('ui-library.invitations.expiration_days', 7)),
        ]);

        $this->sendMail($invitation);
    }

    /**
     * Revoke an invitation.
     */
    public function revoke(Invitation $invitation): void
    {
        $invitation->update([
            'status' => Invitation::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);

        InvitationRevoked::dispatch($invitation);
    }

    /**
     * Expire all pending invitations past their expiration date.
     */
    public function expire(): int
    {
        $expired = Invitation::where('status', Invitation::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $invitation) {
            $invitation->update(['status' => Invitation::STATUS_EXPIRED]);

            InvitationExpired::dispatch($invitation);
        }

        return $expired->count();
    }

    /**
     * Find a valid (pending, not expired) invitation by token.
     */
    public function findValidByToken(string $token): ?Invitation
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', Invitation::STATUS_PENDING)
            ->first();

        if (! $invitation) {
            return null;
        }

        if ($invitation->hasExpired()) {
            $invitation->update(['status' => Invitation::STATUS_EXPIRED]);

            return null;
        }

        return $invitation;
    }

    /**
     * Generate a signed accept URL for an invitation.
     */
    public function generateAcceptUrl(Invitation $invitation): string
    {
        return URL::signedRoute('invitations.accept', ['token' => $invitation->token]);
    }

    /**
     * Send the invitation email.
     */
    protected function sendMail(Invitation $invitation): void
    {
        Mail::to($invitation->email)->queue(
            new InvitationMail($invitation, $this->generateAcceptUrl($invitation))
        );
    }
}