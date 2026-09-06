<x-qf::navigation-layout configKey="admin.permission" context="Access" moduleName="admin" :overrides=[]>
    <div class="row">
        <div class="col-12">
            <ul class="nav nav-tabs" id="accessControlTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="permissions-tab-button" data-bs-toggle="tab" data-bs-target="#permissions-tab" type="button" role="tab" aria-controls="permissions-tab" aria-selected="true">
                        <i class="fas fa-shield-alt me-1"></i> Permissions
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="roles-tab-button" data-bs-toggle="tab" data-bs-target="#roles-tab" type="button" role="tab" aria-controls="roles-tab" aria-selected="false">
                        <i class="fas fa-user-tag me-1"></i> Roles
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="permissions-tab" role="tabpanel" aria-labelledby="permissions-tab-button">
                    <livewire:qf.access-control-manager />
                </div>
                <div class="tab-pane fade" id="roles-tab" role="tabpanel" aria-labelledby="roles-tab-button">
                    <livewire:qf.role-assignment-manager />
                </div>
            </div>
        </div>
    </div>
</x-qf::navigation-layout>
