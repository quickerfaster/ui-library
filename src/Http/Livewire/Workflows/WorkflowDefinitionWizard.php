<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Workflows;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Http\Livewire\Wizards\Wizard;
use QuickerFaster\UILibrary\Models\WorkflowDefinition;
use QuickerFaster\UILibrary\Models\WorkflowDefinitionStep;

/**
 * Workflow definition wizard.
 *
 * Extends the library's Wizard base class for step tracking, session
 * persistence, and cancellation plumbing, but supplies its own five custom
 * (non-model-backed) steps and view:
 *
 *   0. Workflow Details
 *   1. Add Initiators
 *   2. Add Reviewers (serial chain via ReviewerChainBuilder)
 *   3. Add Authorizers
 *   4. Summary / Save
 *
 * ## Step indexing
 *
 * User-facing steps are 1-based (Step 1 through Step 5), while the internal
 * `$currentStep` property is 0-based. The mapping is:
 *
 *   UI (1-based)   $currentStep (0-based)   Step
 *   -----------   -----------------------   ------------------
 *   Step 1        0                        Workflow Details
 *   Step 2        1                        Add Initiators
 *   Step 3        2                        Add Reviewers
 *   Step 4        3                        Add Authorizers
 *   Step 5        4                        Summary / Save
 *
 * The Blade view renders `$index + 1` for the tracker label, and validation
 * switches on the 0-based `$currentStep` (see validateCurrentStep()).
 *
 * ## Authorizer tier — required at wizard, optional at engine
 *
 * The wizard REQUIRES at least one authorizer (validateAssignees() rejects an
 * empty `$authorizers` list), but the WorkflowEngine auto-approves definitions
 * that yield zero runtime approval steps (see WorkflowEngine::start()). This is
 * intentional: normal wizard-created definitions always include an authorizer,
 * so they never hit the auto-approve path. Only manually-authored config
 * definitions with no review/authorizer tiers can trigger auto-approval.
 *
 * ## Summary pipeline vs runtime steps
 *
 * The Summary step renders a visual pipeline that includes an "Initiator" node
 * when initiators are configured. That node is presentation-only: the engine's
 * hydrateFromModel() collects initiators into a separate `initiators` key for
 * submit-time authorization and does NOT create runtime WorkflowStep rows for
 * them (see WorkflowEngine::hydrateFromModel()). Only review and authorizer
 * tiers become runtime approval steps.
 */
class WorkflowDefinitionWizard extends Wizard
{
    public int $definitionId = 0;

    // Step 0 — Workflow Details
    public string $workflowKey = '';
    public string $workflowName = '';
    public string $workflowDescription = '';
    public string $entityType = '';
    public bool $isActive = true;
    public bool $keyManuallyEdited = false;

    // Step 1 — Initiators
    // $initiatorMode is a UI-only filter gating which picker renders. The
    // persisted "mode" is always derived from items via detectMode().
    public string $initiatorMode = 'users';   // users | roles | mixed (UI filter)
    public array $initiators = [];            // [['type' => 'user'|'role', 'id' => int|string, 'label' => string]]

    // Step 2 — Reviewers (bound to the ReviewerChainBuilder child)
    public array $reviewSteps = [];

    // Step 3 — Authorizers
    // $authorizerMode is a UI-only filter; the persisted "mode" is derived.
    public string $authorizerMode = 'users';
    public array $authorizers = [];

    // Notifications configuration (within Step 5 — Summary).
    // Stored as the workflow_definitions.notifications JSON column. The
    // `enabled` flag is derived from whether any individual toggle is on.
    public bool $notifyOnSubmitted = true;
    public bool $notifyOnApproved = true;
    public bool $notifyOnRejected = true;
    public bool $notifyOnRecalled = true;

    // Searchable-select parent surface (user/role pickers).
    public array $fields = [];
    public array $searches = [];
    public array $searchResults = [];
    public array $selectedLabels = [];

    public function mount(?string $configKey = null, ?int $definitionId = null): void
    {
        // The admin wrapper blade embeds this component with no mount params,
        // so a query-string `definitionId` (e.g.
        // /admin/workflow-definition-wizard?definitionId=123) is never
        // passed as a mount argument. Fall back to the request query string.
        if (! $definitionId) {
            $definitionId = request()->query('definitionId');
        }

        $this->configKey = $configKey ?? 'admin.wizards.workflow_definition';
        $this->wizardId = 'workflow-definition-wizard-' . session()->getId();

        $this->primaryModelId = 0;

        $this->steps = [
            ['title' => 'Workflow Details'],
            ['title' => 'Add Initiators'],
            ['title' => 'Add Reviewers'],
            ['title' => 'Add Authorizers'],
            ['title' => 'Summary'],
        ];

        $this->models = ['primary' => WorkflowDefinition::class];

        $this->completion = [
            'title' => 'Workflow Definition Saved',
            'message' => 'The workflow definition is now available to the workflow engine.',
            'actions' => [],
        ];

        $this->title = 'Workflow Definition';
        $this->description = 'Configure who can initiate, review, and authorize an approval workflow.';
        $this->returnPath = '/admin/workflow-definitions';

        $this->reviewSteps = [['name' => '', 'resolution_mode' => 'any', 'assignees' => []]];

        if ($definitionId) {
            $this->definitionId = (int) $definitionId;
            // Editing an existing definition: discard any stale in-progress
            // draft so restoreFromSession() below cannot overwrite the data
            // freshly loaded from the database.
            session()->forget($this->wizardId);
            $this->loadDefinition();
        }

        if (session()->has($this->wizardId)) {
            $this->restoreFromSession();
        }
    }

    public function render()
    {
        return view('qf::livewire.workflows.workflow-definition-wizard', [
            'stepIndex' => $this->currentStep,
            'showCompletion' => $this->currentStep === count($this->steps),
            'userModel' => config('ui-library.user.model', \App\Models\User::class),
            'roleModel' => config('permission.models.role', \Spatie\Permission\Models\Role::class),
        ]);
    }

    // ------------------------------------------------------------------
    // Step navigation
    // ------------------------------------------------------------------

    public function goToStep(int $index): void
    {
        if ($index >= 0 && $index < count($this->steps)) {
            $this->currentStep = $index;
            $this->repopulateCurrentPicker();
            $this->persist();
        }
    }

    public function next(): void
    {
        if ($this->currentStep >= count($this->steps) - 1) {
            return;
        }

        if (! $this->validateCurrentStep()) {
            return;
        }

        $this->currentStep++;
        $this->repopulateCurrentPicker();
        $this->persist();
    }

    public function previous(): void
    {
        if ($this->currentStep > 0) {
            $this->currentStep--;
            $this->repopulateCurrentPicker();
            $this->persist();
        }
    }

    /**
     * Rebuild transient picker state for the step just navigated to.
     *
     * Reviewer state survives independently in the ReviewerChainBuilder child
     * component, but the initiator/authorizer badges live in this wizard's
     * transient $fields / $selectedLabels arrays. Rebuild those from the
     * persistent $initiators / $authorizers whenever we land on their steps so
     * the badges are never lost during back/forward navigation.
     */
    protected function repopulateCurrentPicker(): void
    {
        if ($this->currentStep === 1) {
            $this->repopulatePicker('initiator');
        } elseif ($this->currentStep === 3) {
            $this->repopulatePicker('authorizer');
        }
    }

    public function cancel(): void
    {
        session()->forget($this->wizardId);
        $this->redirect($this->returnPath);
    }

    public function finish(): void
    {
        if (! $this->validateFinalStep()) {
            return;
        }

        $this->saveDefinition();
        session()->forget($this->wizardId);
        $this->currentStep = count($this->steps);
    }

    // ------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------

    protected function validateCurrentStep(): bool
    {
        $this->resetErrorBag();

        return match ($this->currentStep) {
            0 => $this->validateDetails(),
            1 => $this->validateAssignees('initiator', $this->initiators),
            2 => $this->validateReviewSteps(),
            3 => $this->validateAssignees('authorizer', $this->authorizers),
            default => true,
        };
    }

    /**
     * Validate the review steps (Step 2).
     *
     * Review steps are optional — zero steps is valid. If a step has a name,
     * it must have at least one assignee. Steps with an empty name and no
     * assignees are the seeded blank and are silently skipped.
     */
    protected function validateReviewSteps(): bool
    {
        $valid = true;

        foreach ($this->reviewSteps as $index => $step) {
            $hasName = ! empty(trim($step['name'] ?? ''));
            $hasAssignees = ! empty($step['assignees'] ?? []);

            if ($hasName && ! $hasAssignees) {
                $this->addError("reviewSteps.{$index}.assignees", "Review step \"{$step['name']}\" has no assignees. Add at least one reviewer or remove the step.");
                $valid = false;
            }
        }

        return $valid;
    }

    protected function validateDetails(): bool
    {
        $valid = true;

        if (trim($this->workflowName) === '') {
            $this->addError('workflowName', 'A workflow name is required.');
            $valid = false;
        }

        if (trim($this->entityType) === '') {
            $this->addError('entityType', 'An entity type is required.');
            $valid = false;
        }

        if (trim($this->workflowKey) === '') {
            $this->workflowKey = Str::slug($this->workflowName, '_');
        }

        $this->workflowKey = Str::slug($this->workflowKey, '_');

        if (trim($this->workflowKey) === '') {
            $this->addError('workflowKey', 'A workflow key is required.');
            return false;
        }

        $exists = WorkflowDefinition::query()
            ->where('key', $this->workflowKey)
            ->when($this->definitionId, fn ($query) => $query->where('id', '!=', $this->definitionId))
            ->exists();

        if ($exists) {
            $this->addError('workflowKey', 'This workflow key is already in use.');
            return false;
        }

        return $valid;
    }

    protected function validateAssignees(string $tier, array $assignees): bool
    {
        if ($assignees === []) {
            $this->addError("{$tier}Assignees", 'Add at least one assignee.');
            return false;
        }

        return true;
    }

    /**
     * Validate the definition before final save (Finish action).
     *
     * next() validates steps 0–3, but goToStep() lets a user jump directly to
     * the Summary step (index 4). Guard against saving an incomplete definition
     * by re-checking the required fields here and redirecting back to the
     * relevant step on failure.
     */
    protected function validateFinalStep(): bool
    {
        if (trim($this->workflowName) === '') {
            $this->addError('workflowName', 'A workflow name is required.');
            $this->goToStep(0);

            return false;
        }

        if (trim($this->workflowKey) === '') {
            $this->workflowKey = Str::slug($this->workflowName, '_');
        }

        $this->workflowKey = Str::slug($this->workflowKey, '_');

        if (trim($this->workflowKey) === '') {
            $this->addError('workflowKey', 'A workflow key is required.');
            $this->goToStep(0);

            return false;
        }

        if ($this->authorizers === []) {
            $this->addError('authorizerAssignees', 'Add at least one authorizer.');
            $this->goToStep(3);

            return false;
        }

        return true;
    }

    // ------------------------------------------------------------------
    // Auto-slug the workflow key from the name
    // ------------------------------------------------------------------

    public function updatedWorkflowName($value): void
    {
        if (! $this->keyManuallyEdited) {
            $this->workflowKey = Str::slug($value, '_');
        }
    }

    public function updatedWorkflowKey($value): void
    {
        $this->keyManuallyEdited = true;
        $this->workflowKey = Str::slug($value, '_');
    }

    // ------------------------------------------------------------------
    // User / role picker (reuses LivewireSearchableSelectField via FieldFactory)
    // ------------------------------------------------------------------

    // The assignment-mode toggle is a UI-only filter. Switching it re-syncs
    // the picker's transient state via repopulatePicker() but PRESERVES the
    // existing selections — the persisted `mode` is always derived from the
    // actual items, never from this toggle.
    public function updatedInitiatorMode($value): void
    {
        $this->repopulatePicker('initiator');
    }

    public function updatedAuthorizerMode($value): void
    {
        $this->repopulatePicker('authorizer');
    }

    public function getField(string $name): FieldType
    {
        $config = $this->assigneeFieldConfigs()[$name] ?? null;

        if (! $config) {
            throw new \InvalidArgumentException("Unknown assignee field: {$name}");
        }

        return app(FieldFactory::class)->make($name, $config);
    }

    public function updatedSearches($value, $field): void
    {
        $config = $this->assigneeFieldConfigs()[$field] ?? null;

        if (! $config || ! isset($config['relationship'])) {
            return;
        }

        $rel = $config['relationship'];
        $model = $rel['model'];
        $displayField = $rel['display_field'] ?? 'name';
        $searchFields = $rel['search_fields'] ?? [$displayField];

        $query = $model::query();
        $query->where(function ($q) use ($searchFields, $value) {
            foreach ($searchFields as $sf) {
                $q->orWhere($sf, 'LIKE', '%' . $value . '%');
            }
        });

        $items = $query->limit(50)->get();

        $isRoleField = str_ends_with($field, '_roles');

        $results = [];
        foreach ($items as $item) {
            $label = $item->{$displayField} ?? ($isRoleField ? $item->name : $item->getKey());

            // Bug 3: roles are keyed/identified by their name string, while
            // users are identified by their integer primary key.
            $key = $isRoleField ? $label : $item->getKey();

            $results[$key] = $label;
        }

        $this->searchResults[$field] = $results;
    }

    public function selectOption($field, $id, $label): void
    {
        $current = $this->fields[$field] ?? [];

        if (! in_array($id, $current, true)) {
            $this->fields[$field][] = $id;
            $this->selectedLabels[$field][$id] = $label;

            $type = str_ends_with($field, '_roles') ? 'role' : 'user';
            $target = str_starts_with($field, 'authorizer') ? 'authorizers' : 'initiators';

            $this->{$target}[] = ['type' => $type, 'id' => $id, 'label' => $label];
        }

        $this->searches[$field] = '';
        $this->searchResults[$field] = [];
    }

    public function removeSelected($field, $id): void
    {
        $current = $this->fields[$field] ?? [];
        $this->fields[$field] = array_values(array_diff($current, [$id]));
        unset($this->selectedLabels[$field][$id]);

        $type = str_ends_with($field, '_roles') ? 'role' : 'user';
        $target = str_starts_with($field, 'authorizer') ? 'authorizers' : 'initiators';

        $this->{$target} = array_values(array_filter($this->{$target}, function ($item) use ($type, $id) {
            return ! (($item['type'] ?? null) === $type && (string) ($item['id'] ?? null) === (string) $id);
        }));
    }

    protected function assigneeFieldConfigs(): array
    {
        $userModel = config('ui-library.user.model', \App\Models\User::class);
        $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);

        return [
            'initiator_users' => $this->searchableSelectConfig($userModel, 'Users', ['name', 'email']),
            'initiator_roles' => $this->searchableSelectConfig($roleModel, 'Roles', ['name']),
            'authorizer_users' => $this->searchableSelectConfig($userModel, 'Users', ['name', 'email']),
            'authorizer_roles' => $this->searchableSelectConfig($roleModel, 'Roles', ['name']),
        ];
    }

    protected function searchableSelectConfig(string $model, string $label, array $searchFields): array
    {
        return [
            'field_type' => 'livewire-searchable-select',
            'label' => $label,
            'multiSelect' => true,
            'relationship' => [
                'model' => $model,
                'display_field' => 'name',
                'search_fields' => $searchFields,
            ],
        ];
    }

    /**
     * Rebuild a picker's transient state from its persisted selection.
     *
     * The initiator/authorizer badges render from the transient `$fields` and
     * `$selectedLabels` arrays, while `$initiators` / `$authorizers` are the
     * source of truth. This method clears the transient search state and
     * rebuilds `$fields` / `$selectedLabels` from the preserved selection so
     * the selected badges continue to render for whichever picker(s) are
     * visible.
     *
     * Called both when the assignment-mode toggle changes (preserving the
     * existing selection) and when navigation lands on an initiator /
     * authorizer step (restoring the badges after Back/Continue/jump).
     */
    protected function repopulatePicker(string $prefix): void
    {
        $selection = $this->{$prefix . 's'};

        foreach ([$prefix . '_users', $prefix . '_roles'] as $field) {
            $this->fields[$field] = [];
            $this->selectedLabels[$field] = [];
            $this->searches[$field] = '';
            $this->searchResults[$field] = [];
        }

        foreach ($selection as $item) {
            $type = $item['type'] ?? 'user';
            $field = $prefix . ($type === 'role' ? '_roles' : '_users');

            $this->fields[$field][] = $item['id'];
            $this->selectedLabels[$field][$item['id']] = $item['label'];
        }
    }

    // ------------------------------------------------------------------
    // Persistence
    // ------------------------------------------------------------------

    protected function saveDefinition(): void
    {
        $warnings = [];

        DB::transaction(function () use (&$warnings) {
            $definition = $this->definitionId
                ? WorkflowDefinition::findOrFail($this->definitionId)
                : new WorkflowDefinition();

            $definition->fill([
                'key' => $this->workflowKey,
                'name' => $this->workflowName,
                'description' => $this->workflowDescription,
                'entity_type' => $this->entityType,
                'is_active' => $this->isActive,
                'notifications' => [
                    'enabled' => $this->notifyOnSubmitted || $this->notifyOnApproved || $this->notifyOnRejected || $this->notifyOnRecalled,
                    'types' => [
                        'submitted' => $this->notifyOnSubmitted ? 'workflow_submitted' : null,
                        'approved' => $this->notifyOnApproved ? 'workflow_approved' : null,
                        'rejected' => $this->notifyOnRejected ? 'workflow_rejected' : null,
                        'recalled' => $this->notifyOnRecalled ? 'workflow_recalled' : null,
                    ],
                ],
            ]);
            $definition->save();

            $definition->steps()->delete();

            $sequence = 1;

            if ($this->initiators !== []) {
                WorkflowDefinitionStep::create([
                    'workflow_definition_id' => $definition->id,
                    'sequence' => $sequence++,
                    'tier_type' => 'initiator',
                    'name' => 'Initiator',
                    'resolution_mode' => 'any',
                    'assignees' => $this->toAssignees($this->initiators),
                ]);
            }

            foreach ($this->reviewSteps as $reviewStep) {
                if (empty($reviewStep['assignees'] ?? [])) {
                    if (! empty(trim($reviewStep['name'] ?? ''))) {
                        $warnings[] = "Review step \"{$reviewStep['name']}\" was skipped because it has no assignees.";
                    }
                    continue;
                }

                WorkflowDefinitionStep::create([
                    'workflow_definition_id' => $definition->id,
                    'sequence' => $sequence++,
                    'tier_type' => 'review',
                    'name' => $reviewStep['name'] ?: 'Review Step',
                    'resolution_mode' => $reviewStep['resolution_mode'] ?? 'any',
                    'assignees' => $this->toAssignees($reviewStep['assignees']),
                ]);
            }

            if ($this->authorizers !== []) {
                WorkflowDefinitionStep::create([
                    'workflow_definition_id' => $definition->id,
                    'sequence' => $sequence++,
                    'tier_type' => 'authorizer',
                    'name' => 'Authorizer',
                    'resolution_mode' => 'any',
                    'assignees' => $this->toAssignees($this->authorizers),
                ]);
            }

            $this->definitionId = $definition->id;
        });

        foreach ($warnings as $message) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => $message,
            ]);
        }
    }

    protected function loadDefinition(): void
    {
        $definition = WorkflowDefinition::with('steps')->find($this->definitionId);

        if (! $definition) {
            return;
        }

        $this->workflowKey = $definition->key;
        $this->workflowName = $definition->name;
        $this->workflowDescription = $definition->description ?? '';
        $this->entityType = $definition->entity_type;
        $this->isActive = $definition->is_active;

        $notifications = $definition->notifications;

        if (is_array($notifications)) {
            $this->notifyOnSubmitted = !empty($notifications['types']['submitted']);
            $this->notifyOnApproved = !empty($notifications['types']['approved']);
            $this->notifyOnRejected = !empty($notifications['types']['rejected']);
            $this->notifyOnRecalled = !empty($notifications['types']['recalled']);
        }

        $initiators = [];
        $reviewSteps = [];
        $authorizers = [];

        foreach ($definition->steps as $step) {
            $assignees = $this->normalizeAssignees($step);

            if ($step->tier_type === 'initiator') {
                $initiators = $assignees;
            } elseif ($step->tier_type === 'review') {
                $reviewSteps[] = [
                    'name' => $step->name,
                    'resolution_mode' => $step->resolution_mode ?? 'any',
                    'assignees' => $assignees,
                ];
            } elseif ($step->tier_type === 'authorizer') {
                $authorizers = $assignees;
            }
        }

        $this->initiators = $initiators;
        $this->initiatorMode = $this->detectMode($initiators);
        $this->reviewSteps = $reviewSteps !== []
            ? $reviewSteps
            : [['name' => '', 'resolution_mode' => 'any', 'assignees' => []]];
        $this->authorizers = $authorizers;
        $this->authorizerMode = $this->detectMode($authorizers);
    }

    /**
     * Convert stored `{ mode, ids }` assignees into a list of labelled entries.
     *
     * Convention (Bug 5 fix): the ids array is self-describing — integer ids
     * are user IDs and string ids are role names. This mirrors the
     * DefaultApproverResolver and is independent of the stored `mode` value,
     * which is derived/informational and may be stale on older rows.
     */
    protected function normalizeAssignees(WorkflowDefinitionStep $step): array
    {
        $assignees = $step->assignees;

        if (! is_array($assignees)) {
            return [];
        }

        $ids = $assignees['ids'] ?? [];
        $result = [];

        foreach ((array) $ids as $id) {
            $type = is_int($id) || (is_string($id) && ctype_digit($id)) ? 'user' : 'role';
            $result[] = ['type' => $type, 'id' => $id, 'label' => $this->resolveAssigneeLabel($type, $id)];
        }

        return $result;
    }

    /**
     * Convert picker entries into the persisted `{ mode, ids }` shape.
     *
     * `mode` is derived from the entries via detectMode() (never the UI
     * toggle). `ids` follows the self-describing convention: int = user ID,
     * string = role name.
     */
    protected function toAssignees(array $items): array
    {
        $mode = $this->detectMode($items);
        $ids = [];

        foreach ($items as $item) {
            $ids[] = ($item['type'] ?? 'user') === 'role'
                ? $item['id']
                : (int) $item['id'];
        }

        return ['mode' => $mode, 'ids' => $ids];
    }

    protected function detectMode(array $items): string
    {
        $hasUsers = false;
        $hasRoles = false;

        foreach ($items as $item) {
            if (($item['type'] ?? 'user') === 'user') {
                $hasUsers = true;
            } else {
                $hasRoles = true;
            }
        }

        if ($hasUsers && $hasRoles) {
            return 'mixed';
        }

        return $hasRoles ? 'roles' : 'users';
    }

    protected function resolveAssigneeLabel(string $type, $id): string
    {
        if ($type === 'role') {
            $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);
            $role = $roleModel::query()->where('name', $id)->first();

            return $role ? ($role->name ?? (string) $id) : (string) $id;
        }

        $userModel = config('ui-library.user.model', \App\Models\User::class);
        $user = $userModel::find($id);

        return $user ? ($user->name ?? $user->email ?? 'User #' . $id) : 'User #' . $id;
    }

    // ------------------------------------------------------------------
    // Session persistence
    // ------------------------------------------------------------------

    protected function persist(): void
    {
        session()->put($this->wizardId, [
            'definitionId' => $this->definitionId,
            'currentStep' => $this->currentStep,
            'workflowKey' => $this->workflowKey,
            'workflowName' => $this->workflowName,
            'workflowDescription' => $this->workflowDescription,
            'entityType' => $this->entityType,
            'isActive' => $this->isActive,
            'keyManuallyEdited' => $this->keyManuallyEdited,
            'initiatorMode' => $this->initiatorMode,
            'initiators' => $this->initiators,
            'reviewSteps' => $this->reviewSteps,
            'authorizerMode' => $this->authorizerMode,
            'authorizers' => $this->authorizers,
            'notifyOnSubmitted' => $this->notifyOnSubmitted,
            'notifyOnApproved' => $this->notifyOnApproved,
            'notifyOnRejected' => $this->notifyOnRejected,
            'notifyOnRecalled' => $this->notifyOnRecalled,
        ]);
    }

    protected function restoreFromSession(): void
    {
        $data = session()->get($this->wizardId, []);

        $this->definitionId = $data['definitionId'] ?? 0;
        $this->currentStep = $data['currentStep'] ?? 0;
        $this->workflowKey = $data['workflowKey'] ?? '';
        $this->workflowName = $data['workflowName'] ?? '';
        $this->workflowDescription = $data['workflowDescription'] ?? '';
        $this->entityType = $data['entityType'] ?? '';
        $this->isActive = $data['isActive'] ?? true;
        $this->keyManuallyEdited = $data['keyManuallyEdited'] ?? false;
        $this->initiatorMode = $data['initiatorMode'] ?? 'users';
        $this->initiators = $data['initiators'] ?? [];
        $this->reviewSteps = $data['reviewSteps'] ?? [['name' => '', 'resolution_mode' => 'any', 'assignees' => []]];
        $this->authorizerMode = $data['authorizerMode'] ?? 'users';
        $this->authorizers = $data['authorizers'] ?? [];
        $this->notifyOnSubmitted = $data['notifyOnSubmitted'] ?? true;
        $this->notifyOnApproved = $data['notifyOnApproved'] ?? true;
        $this->notifyOnRejected = $data['notifyOnRejected'] ?? true;
        $this->notifyOnRecalled = $data['notifyOnRecalled'] ?? true;
    }

    // ------------------------------------------------------------------
    // Summary pipeline nodes
    // ------------------------------------------------------------------

    /**
     * Return the ordered approval-flow nodes for the Summary timeline.
     *
     * Each node has:
     *  - label:     tier label (Initiator, review step name, Authorizer)
     *  - resolution: human-readable resolution mode
     *  - assignees: array of ['type' => 'user'|'role', 'id' => ..., 'label' => ...]
     *
     * This centralises the tier-ordering logic so the Blade timeline is a
     * clean loop.
     */
    public function pipelineNodes(): array
    {
        $nodes = [];

        if (! empty($this->initiators)) {
            $nodes[] = [
                'label' => 'Initiator',
                'resolution' => 'Any one can submit',
                'assignees' => $this->initiators,
            ];
        }

        foreach ($this->reviewSteps as $reviewStep) {
            if (empty($reviewStep['assignees'] ?? [])) {
                continue;
            }

            $nodes[] = [
                'label' => $reviewStep['name'] ?: 'Review Step',
                'resolution' => ($reviewStep['resolution_mode'] ?? 'any') === 'all'
                    ? 'All must review'
                    : 'Any one can review',
                'assignees' => $reviewStep['assignees'],
            ];
        }

        if (! empty($this->authorizers)) {
            $nodes[] = [
                'label' => 'Authorizer',
                'resolution' => 'Any one can approve',
                'assignees' => $this->authorizers,
            ];
        }

        return $nodes;
    }
}