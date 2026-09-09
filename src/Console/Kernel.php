<?php

/*
|--------------------------------------------------------------------------
| Console Kernel Scheduling Notes
|--------------------------------------------------------------------------
|
| This file documents where consuming applications should register the
| library's scheduled commands inside their own app/Console/Kernel.php.
|
| Scheduled report generation is driven by the active command:
|
|   QuickerFaster\UILibrary\Console\Commands\GenerateScheduledReports
|   (signature: reports:generate-scheduled)
|
| Invitation expiry is driven by:
|
|   QuickerFaster\UILibrary\Console\Commands\ExpireInvitations
|   (signature: invitations:expire)
|
| Invitation reminders are driven by:
|
|   QuickerFaster\UILibrary\Console\Commands\SendInvitationReminders
|   (signature: invitations:send-reminders)
|
| Example registration:
|
|   protected function schedule(Schedule $schedule)
|   {
|       $schedule->command('reports:generate-scheduled')->hourly();
|       $schedule->command('invitations:expire')->daily();
|       $schedule->command('invitations:send-reminders')->dailyAt('09:00');
|   }
|
| Optional config/qf.php retention knob (legacy note):
|
|   return [
|       'export_retention_days' => env('EXPORT_RETENTION_DAYS', 1),
|   ];
|
*/
