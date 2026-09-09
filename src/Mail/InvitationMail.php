<?php

namespace QuickerFaster\UILibrary\Mail;

use QuickerFaster\UILibrary\Models\Invitation;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public string $acceptUrl,
        public string $subjectPrefix = '',
    ) {}

    public function build()
    {
        $companyName = config('app.name', 'QuickerFaster');

        return $this
            ->subject($this->subjectPrefix . "You've been invited to join {$companyName}")
            ->markdown('qf::mail.invitation', [
                'invitation' => $this->invitation,
                'acceptUrl' => $this->acceptUrl,
                'companyName' => $companyName,
                'expiresAt' => $this->invitation->expires_at,
            ]);
    }
}