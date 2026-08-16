<?php

namespace QuickerFaster\UILibrary\Contracts\Notifications;

interface TemplateVariableRegistry
{
    /**
     * Return the available template variables for a given notification type.
     *
     * Returns an associative array of [placeholder => label] pairs.
     *
     * @param  string  $type  The notification type (e.g. 'workflow_approval').
     * @return array<string, string>
     */
    public function variables(string $type): array;
}