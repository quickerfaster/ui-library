<?php

/**
 * Workflow Definition Wizard configuration.
 *
 * Resolved via the wizard config key "admin.wizards.workflow_definition".
 *
 * The WorkflowDefinitionWizard overrides the base Wizard mount/render lifecycle
 * to supply custom, non-model-backed step content, but this file documents the
 * five steps and the completion screen for discoverability and consistency
 * with the other wizard configs.
 */
return [
    'id' => 'workflow_definition',
    'title' => 'Workflow Definition',
    'description' => 'Configure who can initiate, review, and authorize an approval workflow.',
    'returnPath' => '/admin/workflow-definition-wizard',
    'steps' => [
        0 => ['title' => 'Workflow Details'],
        1 => ['title' => 'Add Initiators'],
        2 => ['title' => 'Add Reviewers'],
        3 => ['title' => 'Add Authorizers'],
        4 => ['title' => 'Summary'],
    ],
    'completion' => [
        'title' => 'Workflow Definition Saved',
        'message' => 'The workflow definition is now available to the workflow engine.',
        'actions' => [],
    ],
    'models' => [
        'primary' => 'QuickerFaster\\UILibrary\\Models\\WorkflowDefinition',
    ],
    'linkFields' => [],
];