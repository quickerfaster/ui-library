<?php

namespace QuickerFaster\UILibrary\Traits;

use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;

/**
 * Provides sensible default implementations of the Notifiable contract
 * for Eloquent models (typically the User model).
 *
 * Compose this trait into HasUILibraryUser so every consuming app's User
 * model automatically satisfies the Notifiable interface without any
 * additional boilerplate.
 */
trait HasNotifications
{
    /**
     * Return the model's primary key as the notifiable identifier.
     */
    public function getNotifiableId(): int|string
    {
        return $this->getKey();
    }

    /**
     * Return the model's morph class as the notifiable type.
     */
    public function getNotifiableType(): string
    {
        return $this->getMorphClass();
    }

    /**
     * Return the user's email address for mail-channel notifications.
     * Override in the consuming model if the column is named differently.
     */
    public function getNotificationEmail(): ?string
    {
        return $this->email ?? null;
    }

    /**
     * Return the user's phone number for SMS-channel notifications.
     * Override in the consuming model if phone notifications are needed.
     */
    public function getNotificationPhone(): ?string
    {
        return null;
    }

    /**
     * Return device tokens for push-notification channels.
     * Override in the consuming model if push notifications are needed.
     */
    public function getNotificationDeviceTokens(): array
    {
        return [];
    }
}