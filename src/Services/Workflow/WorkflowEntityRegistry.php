<?php

namespace QuickerFaster\UILibrary\Services\Workflow;

/**
 * WorkflowEntityRegistry — resolves known workflow entity types.
 *
 * Reads from config('ui-library.workflows.entity_types') which maps
 * workflow definition keys to human-readable labels. Consuming-app
 * modules register their Workflowable entities here so the Workflow
 * Definition Wizard can offer a validated dropdown.
 *
 * Usage:
 *   $registry = app(WorkflowEntityRegistry::class);
 *   $types = $registry->all();        // ['leave_request' => 'Leave Request', ...]
 *   $label = $registry->label('x');   // 'Leave Request' or null
 *   $valid = $registry->has('x');     // bool
 */
class WorkflowEntityRegistry
{
    /**
     * Get all registered entity types (key => label).
     */
    public function all(): array
    {
        return config('ui-library.workflows.entity_types', []);
    }

    /**
     * Get the human-readable label for a workflow key, or null if unknown.
     */
    public function label(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Check whether a workflow key is a known entity type.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Get workflow keys that match a given entity label (case-insensitive).
     */
    public function findByLabel(string $label): ?string
    {
        foreach ($this->all() as $key => $entityLabel) {
            if (strcasecmp($entityLabel, $label) === 0) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Get the suggested workflow key for a given entity label.
     */
    public function suggestedKey(string $label): string
    {
        $found = $this->findByLabel($label);

        return $found ?? \Illuminate\Support\Str::slug($label, '_');
    }
}