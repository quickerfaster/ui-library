<?php

namespace QuickerFaster\UILibrary\Services\Notifications;

use QuickerFaster\UILibrary\Contracts\Notifications\NotificationAction;
use QuickerFaster\UILibrary\Models\Notification;
use InvalidArgumentException;

class NotificationActionRegistry
{
    /**
     * Registered action handlers keyed by handler name.
     *
     * @var array<string, NotificationAction>
     */
    protected array $actions = [];

    /**
     * Register an action handler.
     */
    public function register(string $handler, NotificationAction $action): void
    {
        $this->actions[$handler] = $action;
    }

    /**
     * Get a registered action handler by name.
     */
    public function get(string $handler): ?NotificationAction
    {
        return $this->actions[$handler] ?? null;
    }

    /**
     * Handle an action for the given notification.
     *
     * @throws InvalidArgumentException if the handler is not registered.
     */
    public function handle(string $handler, Notification $notification, array $data): void
    {
        $action = $this->get($handler);

        if (! $action) {
            throw new InvalidArgumentException("No action handler registered for [{$handler}].");
        }

        $action->handle($notification, $data);
    }
}