<?php

namespace QuickerFaster\UILibrary\Services\Notifications;

/**
 * Maps notification type strings to their icon and colour classes.
 *
 * This central registry keeps the bell drawer, the full notifications page,
 * and any future notification surface visually consistent.
 */
class NotificationTypeRegistry
{
    /**
     * Type => icon/colour mapping.
     */
    protected const TYPES = [
        'workflow_submitted' => ['icon' => 'fas fa-paper-plane', 'color' => 'text-info'],
        'workflow_approved' => ['icon' => 'fas fa-check-circle', 'color' => 'text-success'],
        'workflow_rejected' => ['icon' => 'fas fa-times-circle', 'color' => 'text-danger'],
        'workflow_recalled' => ['icon' => 'fas fa-undo', 'color' => 'text-warning'],
        'document_generated' => ['icon' => 'fas fa-file', 'color' => 'text-primary'],
        'report_ready' => ['icon' => 'fas fa-chart-bar', 'color' => 'text-primary'],
        'workflow_stage_changed' => ['icon' => 'fas fa-exchange-alt', 'color' => 'text-info'],
        'default' => ['icon' => 'fas fa-bell', 'color' => 'text-secondary'],
    ];

    /**
     * Resolve the Font Awesome icon class for a notification type.
     */
    public static function getIcon(string $type): string
    {
        return self::TYPES[$type]['icon'] ?? self::TYPES['default']['icon'];
    }

    /**
     * Resolve the Bootstrap text colour class for a notification type.
     */
    public static function getColor(string $type): string
    {
        return self::TYPES[$type]['color'] ?? self::TYPES['default']['color'];
    }

    /**
     * Return the list of known notification types (excluding the default).
     *
     * @return array<int, string>
     */
    public static function types(): array
    {
        return array_values(array_filter(
            array_keys(self::TYPES),
            fn (string $key) => $key !== 'default',
        ));
    }

    /**
     * Return the full type => icon/colour mapping.
     */
    public static function all(): array
    {
        return self::TYPES;
    }
}
