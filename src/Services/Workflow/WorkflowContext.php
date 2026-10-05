<?php

namespace QuickerFaster\UILibrary\Services\Workflow;

/**
 * WorkflowContext — request-level singleton that carries the current
 * workflow's context data so that contextual role resolvers (e.g.
 * "employee_manager") can access the submitting employee's ID without
 * changing the ApproverResolver contract.
 *
 * Set by WorkflowEngine before resolving step recipients; read by
 * consuming-app ApproverResolver implementations.
 */
class WorkflowContext
{
    /** @var array<string, mixed>|null */
    private ?array $context = null;

    /**
     * Set the current workflow context.
     */
    public function set(?array $context): void
    {
        $this->context = $context;
    }

    /**
     * Get the current workflow context, or null if not set.
     *
     * @return array<string, mixed>|null
     */
    public function get(): ?array
    {
        return $this->context;
    }

    /**
     * Get a specific value from the workflow context.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Clear the context (called after resolution is complete).
     */
    public function clear(): void
    {
        $this->context = null;
    }
}