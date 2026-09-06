{{-- @deprecated Replaced by the generic DataTable component (`qf.data-table`)
     with config key `admin.workflow_definition`. See
     `src/Core/Admin/Data/workflow_definition.php` for the config. --}}
<div class="qf-workflow-definition-list py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Workflow Definitions</h5>
            <a href="/admin/workflow-definition-wizard" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> New Workflow
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Key</th>
                            <th>Entity Type</th>
                            <th>Status</th>
                            <th>Steps</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($definitions as $definition)
                            <tr>
                                <td class="fw-semibold">{{ $definition->name }}</td>
                                <td><code>{{ $definition->key }}</code></td>
                                <td>{{ $definition->entity_type }}</td>
                                <td>
                                    @if ($definition->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $definition->steps_count }}</td>
                                <td class="text-end">
                                    <a href="/admin/workflow-definition-wizard?definitionId={{ $definition->id }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-pen me-1"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No workflow definitions yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
