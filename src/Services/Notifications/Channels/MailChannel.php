<?php

namespace QuickerFaster\UILibrary\Services\Notifications\Channels;

use QuickerFaster\UILibrary\Contracts\Notifications\NotificationChannel;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Models\Notification;
use Illuminate\Support\Facades\Mail;

class MailChannel implements NotificationChannel
{
    public function send(Notification $notification, Notifiable $notifiable, array $data): bool
    {
        $email = $notifiable->getNotificationEmail();
        if (!$email) return false;

        try {
            Mail::raw($notification->body, function ($message) use ($notification, $email) {
                $message->to($email)->subject($notification->subject ?? 'Notification');
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'mail';
    }
}