<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Approvals;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverLabelResolver;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Services\Approvals\ApprovalGuard;

/**
 * Generic, config-driven list of workflow requests.
 *
 * Supports two queue views:
 *   - 'pending':   workflows awaiting action from the current user.
 *   - 'submitted': workflows initiated by the current user.
 *
 * Columns are driven by config('ui-library.approvals.list_columns').
 */
class ApprovalRequestListView extends Component
{
    use WithPagination;

    public string $view = 'pending';

    public ?string $workflowKey = null;

    public ?string $status = null;

    public int $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['refreshApprovalRequests' => '$refresh'];

    public function __construct(
        protected ApprovalGuard $guard,
        protected ApproverLabelResolver $labels,
    ) {
    }

    public function mount(string $view = 'pending', ?string $workflowKey = null, ?string $status = null): void
    {
        $this->view = in_array($view, ['pending', 'submitted'], true) ? $view : 'pending';
        $this->workflowKey = $workflowKey;
        $this->status = $status;
    }

    public function updatedView(): void
    {
        $this->resetPage();
    }

    public function updatedWorkflowKey(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function selectWorkflow(int $workflowId): void
    {
        $this->dispatch('showApprovalRequest', $workflowId);
    }

    public function workflowLabel(Workflow $workflow): string
    {
        $definition = config("ui-library.workflows.definitions.{$workflow->definition_key}");

        if (is_array($definition) && isset($definition['label'])) {
            return (string) $definition['label'];
        }

        return $workflow->definition_key ?: 'Workflow';
    }

    public function entityReference(Workflow $workflow): string
    {
        try {
            $entity = $workflow->workflowable;
        } catch (\Throwable $e) {
            $entity = null;
        }

        if ($entity) {
            foreach (['name', 'title', 'reference', 'code'] as $attribute) {
                if (isset($entity->{$attribute}) && $entity->{$attribute} !== null && $entity->{$attribute} !== '') {
                    return (string) $entity->{$attribute};
                }
            }
        }

        $type = $workflow->workflowable_type ? class_basename($workflow->workflowable_type) : 'Entity';

        return $type . ' #' . $workflow->workflowable_id;
    }

    public function render()
    {
        $workflows = $this->buildQuery()->paginate($this->perPage);

        return view('qf::livewire.approvals.approval-request-list', [
            'workflows' => $workflows,
            'columns' => $this->enabledColumns(),
        ]);
    }

    protected function buildQuery()
    {
        $user = Auth::user();

        $query = Workflow::query()->with(['currentStep']);

        if ($this->view === 'pending') {
            $query->where('status', 'pending');
            $this->scopePendingToApprover($query, $user);
        } else {
            $query->where('submitted_by', $user?->getAuthIdentifier());
        }

        if ($this->workflowKey !== null && $this->workflowKey !== '') {
            $query->where('definition_key', $this->workflowKey);
        }

        if ($this->status !== null && $this->status !== '') {
            $query->where('status', $this->status);
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * Scope a "pending" queue to the workflows the given user can approve.
     *
     * Final per-action authorization is still enforced by ApprovalGuard inside
     * WorkflowEngine; this query narrowing provides an efficient, best-effort
     * list of the workflows most likely to require the user's attention.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $user
     */
    protected function scopePendingToApprover($query, $user): void
    {
        if (! $user) {
            $query->whereRaw('1 = 0');

            return;
        }

        // Bypass users (e.g. super admins) see every pending workflow.
        // ApprovalGuard::canApprove with an empty role list only returns true
        // for those bypass users.
        if ($this->guard->canApprove($user, [])) {
            return;
        }

        $roles = $this->userRoleIdentifiers($user);

        if ($roles === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('currentStep', function ($query) use ($roles) {
            $query->where('status', 'pending')->where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhereJsonContains('roles', $role);
                }
            });
        });
    }

    /**
     * Collect the role identifiers held by the user (names and primary keys).
     *
     * @param  mixed  $user
     */
    protected function userRoleIdentifiers($user): array
    {
        $identifiers = [];

        if (method_exists($user, 'getRoleNames')) {
            foreach ($user->getRoleNames() as $name) {
                $identifiers[] = (string) $name;
            }
        }

        if (method_exists($user, 'roles')) {
            foreach ($user->roles as $role) {
                $identifiers[] = (string) $role->getKey();
            }
        }

        return array_values(array_unique(array_filter($identifiers)));
    }

    protected function enabledColumns(): array
    {
        $defaults = $this->defaultColumns();

        $columns = (array) config('ui-library.approvals.list_columns', $defaults);

        if ($columns === []) {
            $columns = $defaults;
        }

        $enabled = [];

        foreach ($columns as $key => $config) {
            $isEnabled = is_array($config) ? ($config['enabled'] ?? true) : (bool) $config;

            if (! $isEnabled) {
                continue;
            }

            $label = is_array($config)
                ? ($config['label'] ?? ($defaults[$key]['label'] ?? $key))
                : ($defaults[$key]['label'] ?? $key);

            $enabled[$key] = ['label' => $label];
        }

        return $enabled;
    }

    protected function defaultColumns(): array
    {
        return [
            'workflow' => ['label' => 'Workflow', 'enabled' => true],
            'entity' => ['label' => 'Entity', 'enabled' => true],
            'current_step' => ['label' => 'Current Step', 'enabled' => true],
            'status' => ['label' => 'Status', 'enabled' => true],
            'submitted_at' => ['label' => 'Submitted', 'enabled' => true],
            'actions' => ['label' => 'Actions', 'enabled' => true],
        ];
    }
}
