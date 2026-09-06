<x-qf::dashboards.default-dashboard>
    @hasanyrole(\QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::ADMIN_ROLES)
        <x-slot name="mainTitle">Assign Roles to Users</x-slot>
        <x-slot name="subtitle">Select a user and choose which roles they should have.</x-slot>

        <div class="card shadow-sm">
            <div class="card-body">
                @if($showSuccess)
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ $successMessage }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="userSelect" class="form-label">Select User</label>
                        <select id="userSelect" wire:model.live="selectedUserId" class="form-select">
                            <option value="">-- Choose a user --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($selectedUserId)
                    <div class="mt-4">
                        <h5>Roles for <strong>{{ $users->find($selectedUserId)->name }}</strong></h5>
                        <div class="row mt-3">
                            @foreach($allRoles as $role)
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="role_{{ $role->id }}"
                                            value="{{ $role->id }}"
                                            wire:model.live="assignedRoleIds"
                                        >
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            {{ $role->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <button wire:click="saveRoles" class="btn btn-primary rounded-pill px-4">
                                <i class="fas fa-save me-2"></i> Save Role Assignment
                            </button>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info mt-3">
                        Please select a user to assign or modify roles.
                    </div>
                @endif
            </div>
        </div>
    @endhasanyrole
</x-qf::dashboards.default-dashboard>