<?php

namespace QuickerFaster\UILibrary\Services\Workflow;

use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Models\WorkflowStep;
use QuickerFaster\UILibrary\Models\WorkflowAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WorkflowEngine
{
    /**
     * Start a new workflow for the given workflowable entity.
     */
    public function start(Workflowable $entity, array $context = []): Workflow
    {
        $definitionKey = $entity->getWorkflowDefinitionKey();
        $definition = $this->getDefinition($definitionKey);

        if (!$definition) {
            throw new \InvalidArgumentException("Workflow definition '{$definitionKey}' not found.");
        }

        return DB::transaction(function () use ($entity, $definitionKey, $definition, $context) {
            $workflow = Workflow::create([
                'workflowable_type' => get_class($entity),
                'workflowable_id' => $entity->getWorkflowableId(),
                'definition_key' => $definitionKey,
                'status' => 'pending',
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'context' => array_merge($entity->getWorkflowContext(), $context),
            ]);

            // Create steps from definition
            $sequence = 1;
            foreach ($definition['steps'] as $stepConfig) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'name' => $stepConfig['name'],
                    'sequence' => $sequence++,
                    'step_type' => $stepConfig['step_type'] ?? 'approval',
                    'approval_mode' => $stepConfig['approval_mode'] ?? 'any',
                    'roles' => $stepConfig['roles'] ?? [],
                    'status' => 'pending',
                ]);
            }

            // Set current step to first step
            $firstStep = $workflow->steps()->orderBy('sequence')->first();
            $workflow->current_step = $firstStep?->id;
            $workflow->save();

            // Log action
            $this->logAction($workflow, null, 'submitted');

            return $workflow;
        });
    }

    /**
     * Approve the current step and advance the workflow.
     */
    public function approve(Workflow $workflow, ?string $comments = null): void
    {
        if (!$workflow->isPending()) {
            throw new \RuntimeException("Workflow is not pending.");
        }

        $currentStep = $workflow->currentStep;
        if (!$currentStep || !$currentStep->isPending()) {
            throw new \RuntimeException("No pending step to approve.");
        }

        DB::transaction(function () use ($workflow, $currentStep, $comments) {
            $currentStep->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'comments' => $comments,
            ]);

            $this->logAction($workflow, $currentStep, 'approved', $comments);
            $this->advanceToNextStep($workflow);
        });
    }

    /**
     * Reject the current step and terminate the workflow.
     */
    public function reject(Workflow $workflow, ?string $comments = null): void
    {
        if (!$workflow->isPending()) {
            throw new \RuntimeException("Workflow is not pending.");
        }

        DB::transaction(function () use ($workflow, $comments) {
            $currentStep = $workflow->currentStep;
            if ($currentStep) {
                $currentStep->update(['status' => 'rejected', 'comments' => $comments]);
            }

            $workflow->update([
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            $this->logAction($workflow, $currentStep, 'rejected', $comments);
        });
    }

    /**
     * Recall (cancel) a pending workflow.
     */
    public function recall(Workflow $workflow): void
    {
        if (!$workflow->isPending()) {
            throw new \RuntimeException("Only pending workflows can be recalled.");
        }

        DB::transaction(function () use ($workflow) {
            $workflow->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            $this->logAction($workflow, null, 'recalled');
        });
    }

    /**
     * Get the workflow definition from config.
     */
    public function getDefinition(string $key): ?array
    {
        return config("ui-library.workflows.definitions.{$key}");
    }

    /**
     * Check if an entity already has an active workflow.
     */
    public function hasActiveWorkflow(Workflowable $entity): bool
    {
        return Workflow::where('workflowable_type', get_class($entity))
            ->where('workflowable_id', $entity->getWorkflowableId())
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Advance to the next pending step, or complete the workflow.
     */
    protected function advanceToNextStep(Workflow $workflow): void
    {
        $nextStep = $workflow->steps()
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();

        if ($nextStep) {
            $workflow->current_step = $nextStep->id;
            $workflow->save();
        } else {
            $workflow->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
            $this->logAction($workflow, null, 'completed');
        }
    }

    /**
     * Log a workflow action.
     */
    protected function logAction(Workflow $workflow, ?WorkflowStep $step, string $action, ?string $comments = null): void
    {
        WorkflowAction::create([
            'workflow_id' => $workflow->id,
            'step_id' => $step?->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'comments' => $comments,
        ]);
    }
}
