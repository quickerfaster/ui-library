<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Workflows;

use Livewire\Attributes\Modelable;
use Livewire\Component;

/**
 * Reusable reviewer chain builder.
 *
 * Renders an ordered list of review steps, each with a name, resolution mode
 * (any / all), and a list of assignees (users or roles). The parent component
 * binds its review-steps array via wire:model.
 *
 * ## Two distinct "mode" concepts
 *
 * Each review step carries two independent mode values that are easily confused:
 *
 * | Field              | Purpose                                              | Values          |
 * |--------------------|------------------------------------------------------|-----------------|
 * | `resolution_mode`  | How many assignees must act before the step advances | `any` or `all`  |
 * | `assignees[].type` | Whether an individual assignee is a user or role     | `user` or `role`|
 *
 * `resolution_mode` is the **runtime enforcement** mode — it controls whether
 * the WorkflowEngine requires one (`any`) or every (`all`) assignee to approve
 * before advancing to the next step.
 *
 * `assignees[].type` describes the **type of each individual assignee** (user
 * vs. role). The parent wizard derives an informational `assignees.mode` field
 * (`users`, `roles`, `mixed`) from the aggregate of these types, but that
 * derived mode has no effect on runtime behavior.
 *
 * Usage:
 *   <livewire:qf.reviewer-chain-builder wire:model="reviewSteps" />
 */
class ReviewerChainBuilder extends Component
{
    #[Modelable]
    public array $value = [];

    public array $searches = [];
    public array $searchResults = [];

    public function addStep(): void
    {
        $this->value[] = ['name' => '', 'resolution_mode' => 'any', 'assignees' => []];
    }

    public function removeStep(int $index): void
    {
        unset($this->value[$index]);
        $this->value = array_values($this->value);
    }

    public function moveStep(int $index, string $direction): void
    {
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($this->value[$target])) {
            return;
        }

        $temp = $this->value[$index];
        $this->value[$index] = $this->value[$target];
        $this->value[$target] = $temp;
    }

    public function addAssignee(int $stepIndex, string $type, $id, string $label): void
    {
        if (! isset($this->value[$stepIndex])) {
            return;
        }

        foreach ($this->value[$stepIndex]['assignees'] ?? [] as $assignee) {
            if (
                ($assignee['type'] ?? null) === $type
                && (string) ($assignee['id'] ?? null) === (string) $id
            ) {
                return; // already added
            }
        }

        $this->value[$stepIndex]['assignees'][] = [
            'type' => $type,
            'id' => $id,
            'label' => $label,
        ];
    }

    public function removeAssignee(int $stepIndex, int $assigneeIndex): void
    {
        if (! isset($this->value[$stepIndex]['assignees'][$assigneeIndex])) {
            return;
        }

        unset($this->value[$stepIndex]['assignees'][$assigneeIndex]);
        $this->value[$stepIndex]['assignees'] = array_values($this->value[$stepIndex]['assignees']);
    }

    /**
     * Add an assignee from a search result index (avoids inline quoting).
     */
    public function addFromSearch(int $stepIndex, int $resultIndex): void
    {
        $result = $this->searchResults[$stepIndex][$resultIndex] ?? null;

        if (! $result) {
            return;
        }

        $this->addAssignee($stepIndex, $result['type'], $result['id'], $result['label']);

        $this->searches[$stepIndex] = '';
        $this->searchResults[$stepIndex] = [];
    }

    public function updatedSearches($value, $key): void
    {
        $stepIndex = (int) $key;
        $this->searchResults[$stepIndex] = $this->performSearch($value);
    }

    protected function performSearch(string $term): array
    {
        $results = [];

        if (trim($term) === '') {
            return $results;
        }

        $userModel = config('ui-library.user.model', \App\Models\User::class);

        if (class_exists($userModel)) {
            $users = $userModel::query()
                ->where('name', 'like', '%' . $term . '%')
                ->orWhere('email', 'like', '%' . $term . '%')
                ->limit(25)
                ->get();

            foreach ($users as $user) {
                $results[] = [
                    'type' => 'user',
                    'id' => $user->getKey(),
                    'label' => $user->name ?? $user->email ?? 'User #' . $user->getKey(),
                ];
            }
        }

        $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);

        if (class_exists($roleModel)) {
            $roles = $roleModel::query()
                ->where('name', 'like', '%' . $term . '%')
                ->limit(25)
                ->get();

            foreach ($roles as $role) {
                $results[] = [
                    'type' => 'role',
                    'id' => $role->name,
                    'label' => $role->name,
                ];
            }
        }

        return $results;
    }

    public function render()
    {
        return view('qf::livewire.workflows.reviewer-chain-builder');
    }
}