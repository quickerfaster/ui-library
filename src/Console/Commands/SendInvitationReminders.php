<?php

namespace QuickerFaster\UILibrary\Console\Commands;

use Illuminate\Console\Command;
use QuickerFaster\UILibrary\Services\Invitations\InvitationService;

class SendInvitationReminders extends Command
{
    protected $signature = 'invitations:send-reminders';

    protected $description = 'Send reminder emails for pending invitations approaching expiration';

    public function handle(InvitationService $service): int
    {
        $pending = $service->getPendingReminders();

        if ($pending->isEmpty()) {
            $this->info('No pending invitations need reminders.');

            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($pending as $invitation) {
            if ($service->sendReminder($invitation)) {
                $count++;
            }
        }

        $this->info("Sent {$count} invitation reminder(s).");

        return Command::SUCCESS;
    }
}