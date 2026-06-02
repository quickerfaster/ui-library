<?php

namespace QuickerFaster\UILibrary\Http\Livewire\AccessControls;

use Livewire\Component;
use App\Models\User;
use App\Modules\Admin\Models\Role;
use Illuminate\Support\Collection;

class RoleAssignmentManager extends Component
{
    public ?int $selectedUserId = null;
    public Collection $users;
    public Collection $allRoles;
    public array $assignedRoleIds = [];
    public bool $showSuccess = false;
    public string $successMessage = '';
    public string $errorMessage = '';

    public function mount()
    {
        $this->users = $this->getManageableUsers();
        $this->allRoles = $this->getAssignableRoles();
    }

    /**
     * Users that the current admin is allowed to modify.
     */
    protected function getManageableUsers(): Collection
    {
        $currentUser = auth()->user();
        $allUsers = User::with('roles')->orderBy('name')->get();

        // Super admin can manage everyone
        if ($currentUser->hasRole('super_admin')) {
            return $allUsers;
        }

        // Company admin: cannot manage users with super_admin or company_admin roles
        $excludedRoleNames = ['super_admin', 'company_admin'];

        return $allUsers->reject(function ($user) use ($excludedRoleNames) {
            return $user->roles->contains(fn($role) => in_array($role->name, $excludedRoleNames));
        });
    }

    /**
     * Roles that the current admin is allowed to assign.
     */
    protected function getAssignableRoles(): Collection
    {
        $currentUser = auth()->user();
        $allRoles = Role::orderBy('name')->get();

        if ($currentUser->hasRole('super_admin')) {
            return $allRoles;
        }

        // Company admin cannot assign super_admin or company_admin roles
        $forbiddenRoles = ['super_admin', 'company_admin'];

        return $allRoles->reject(fn($role) => in_array($role->name, $forbiddenRoles));
    }

    public function updatedSelectedUserId($userId)
    {
        $this->showSuccess = false;
        $this->errorMessage = '';

        if (!$userId) {
            $this->assignedRoleIds = [];
            return;
        }

        $user = User::with('roles')->find($userId);
        if (!$this->isUserManageable($user)) {
            $this->errorMessage = 'You are not allowed to assign roles to this user.';
            $this->selectedUserId = null;
            $this->assignedRoleIds = [];
            return;
        }

        $this->assignedRoleIds = $user->roles->pluck('id')->toArray();
    }

    protected function isUserManageable($user): bool
    {
        $currentUser = auth()->user();
        if ($currentUser->hasRole('super_admin')) {
            return true;
        }

        // Non-super admin cannot manage a super_admin user
        if ($user->hasRole('super_admin')) {
            return false;
        }

        // Company admin cannot manage another company_admin user
        if ($currentUser->hasRole('company_admin') && $user->hasRole('company_admin')) {
            return false;
        }

        return true;
    }

    public function saveRoles()
    {
        $this->errorMessage = '';

        if (!$this->selectedUserId) {
            $this->errorMessage = 'Please select a user.';
            return;
        }

        $user = User::with('roles')->find($this->selectedUserId);
        if (!$this->isUserManageable($user)) {
            $this->errorMessage = 'You are not allowed to modify roles for this user.';
            return;
        }

        $allowedRoleIds = $this->getAssignableRoles()->pluck('id')->toArray();
        $safeRoleIds = array_intersect($this->assignedRoleIds, $allowedRoleIds);
        $roleNames = Role::whereIn('id', $safeRoleIds)->pluck('name')->toArray();

        $user->syncRoles($roleNames);

        $this->successMessage = "Roles for {$user->name} have been updated.";
        $this->showSuccess = true;
    }

    public function render()
    {
        return view('qf::livewire.access-controls.role-assignment-manager');
    }
}