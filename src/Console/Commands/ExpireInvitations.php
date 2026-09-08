<?php

namespace QuickerFaster\UILibrary\Console\Commands;

use Illuminate\Console\Command;
use QuickerFaster\UILibrary\Services\Invitations\InvitationService;

class ExpireInvitations extends Command
{
    protected $signature = 'invitations:expire';

    protected $description = 'Expire all pending invitations past their expiration date';

    public function handle(InvitationService $service): int
    {
        $count = $service->expire();

        $this->info("Expired {$count} invitation(s).");

        return Command::SUCCESS;
    }
}