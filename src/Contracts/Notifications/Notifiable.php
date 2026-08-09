<?php

namespace QuickerFaster\UILibrary\Contracts\Notifications;

interface Notifiable
{
    public function getNotifiableId(): int|string;
    public function getNotifiableType(): string;
    public function getNotificationEmail(): ?string;
    public function getNotificationPhone(): ?string;
    public function getNotificationDeviceTokens(): array;
}