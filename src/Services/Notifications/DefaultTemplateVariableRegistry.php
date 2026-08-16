<?php

namespace QuickerFaster\UILibrary\Services\Notifications;

use QuickerFaster\UILibrary\Contracts\Notifications\TemplateVariableRegistry;

class DefaultTemplateVariableRegistry implements TemplateVariableRegistry
{
    /**
     * Default variable definitions for known notification types.
     *
     * @var array<string, array<string, string>>
     */
    protected array $defaults = [
        'workflow_approval' => [
            'workflow_id' => 'Workflow ID',
            'definition_key' => 'Definition Key',
            'step_name' => 'Step Name',
            'requester_name' => 'Requester Name',
        ],
        'workflow_approved' => [
            'workflow_id' => 'Workflow ID',
            'definition_key' => 'Definition Key',
            'approver_name' => 'Approver Name',
        ],
        'workflow_denied' => [
            'workflow_id' => 'Workflow ID',
            'definition_key' => 'Definition Key',
            'denier_name' => 'Denier Name',
            'reason' => 'Reason',
        ],
        'workflow_cancelled' => [
            'workflow_id' => 'Workflow ID',
            'definition_key' => 'Definition Key',
        ],
        'user_welcome' => [
            'user_name' => 'User Name',
            'user_email' => 'User Email',
        ],
        'user_password_reset' => [
            'user_name' => 'User Name',
            'reset_link' => 'Reset Link',
        ],
    ];

    /**
     * @inheritDoc
     */
    public function variables(string $type): array
    {
        // Check config overrides first, then fall back to built-in defaults.
        $configOverrides = config("ui-library.notifications.template_variables.{$type}", []);

        if (! empty($configOverrides)) {
            return $configOverrides;
        }

        return $this->defaults[$type] ?? [];
    }
}