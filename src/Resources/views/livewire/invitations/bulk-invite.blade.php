<div class="bulk-invite-container">
    @if ($submitted)
        {{-- Success State --}}
        <div class="text-center py-4">
            <div class="mb-3">
                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
            </div>
            <h5 class="fw-bold">Invitations Sent!</h5>
            <p class="text-muted mb-4">
                {{ $sentCount }} invitation{{ $sentCount !== 1 ? 's' : '' }} sent successfully.
            </p>
            <button type="button" class="btn btn-primary" wire:click="resetForm">
                <i class="fas fa-plus me-1"></i> Send More Invitations
            </button>
        </div>
    @else
        {{-- Tab Switcher --}}
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link {{ $activeTab === 'paste' ? 'active' : '' }}"
                    wire:click="switchTab('paste')"
                    role="tab">
                    <i class="fas fa-list me-1"></i> Paste Emails
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link {{ $activeTab === 'csv' ? 'active' : '' }}"
                    wire:click="switchTab('csv')"
                    role="tab">
                    <i class="fas fa-file-csv me-1"></i> Upload CSV
                </button>
            </li>
        </ul>

        {{-- Paste Emails Tab --}}
        @if ($activeTab === 'paste')
            <form wire:submit.prevent="submitPaste">
                {{-- Email Addresses --}}
                <div class="mb-3">
                    <label for="bulk-emails" class="form-label fw-semibold">
                        Email Addresses <span class="text-danger">*</span>
                    </label>
                    <textarea
                        id="bulk-emails"
                        wire:model.lazy="emails"
                        class="form-control @error('emails') is-invalid @enderror"
                        rows="6"
                        placeholder="Enter one email address per line&#10;e.g.&#10;john@example.com&#10;jane@example.com"
                    ></textarea>
                    @error('emails')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        @if ($this->emailCount > 0)
                            <span class="text-success fw-medium">{{ $this->emailCount }} valid email{{ $this->emailCount !== 1 ? 's' : '' }} detected.</span>
                        @else
                            Enter one email address per line.
                        @endif
                    </div>
                </div>

                {{-- Role --}}
                <div class="mb-3">
                    <label for="bulk-role" class="form-label fw-semibold">
                        Role <span class="text-danger">*</span>
                    </label>
                    <select
                        id="bulk-role"
                        wire:model="role"
                        class="form-select @error('role') is-invalid @enderror"
                    >
                        <option value="">Select a role...</option>
                        @foreach ($availableRoles as $roleId => $roleName)
                            <option value="{{ $roleId }}">{{ $roleName }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Optional Message --}}
                <div class="mb-4">
                    <label for="bulk-message" class="form-label fw-semibold">
                        Personal Message <small class="text-muted fw-normal">(optional)</small>
                    </label>
                    <textarea
                        id="bulk-message"
                        wire:model.lazy="message"
                        class="form-control @error('message') is-invalid @enderror"
                        rows="3"
                        placeholder="Add a personal message to include in each invitation email..."
                    ></textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Submit --}}
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitPaste">
                            <i class="fas fa-paper-plane me-1"></i>
                            Send {{ $this->emailCount > 0 ? $this->emailCount : '' }} Invitation{{ $this->emailCount !== 1 ? 's' : '' }}
                        </span>
                        <span wire:loading wire:target="submitPaste">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Sending...
                        </span>
                    </button>
                </div>
            </form>
        @endif

        {{-- CSV Upload Tab --}}
        @if ($activeTab === 'csv')
            <form wire:submit.prevent="submitCsv">
                {{-- CSV File Upload --}}
                <div class="mb-3">
                    <label for="csv-file" class="form-label fw-semibold">
                        CSV File <span class="text-danger">*</span>
                    </label>
                    <div class="border rounded p-4 text-center bg-light"
                        x-data="{ dragging: false }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                        :class="{ 'border-primary bg-primary bg-opacity-10': dragging }">
                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                        <p class="mb-2 text-muted">Drag and drop a CSV file here, or click to browse</p>
                        <input type="file"
                            id="csv-file"
                            x-ref="fileInput"
                            wire:model="csvFile"
                            accept=".csv,.txt"
                            class="form-control @error('csvFile') is-invalid @enderror"
                        />
                        @error('csvFile')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div wire:loading wire:target="csvFile" class="mt-2">
                            <span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span>
                            Parsing CSV...
                        </div>
                    </div>
                    <div class="form-text">
                        CSV should contain columns for email, role, and optionally a personal message.
                    </div>
                </div>

                {{-- Column Mapping (shown after CSV is parsed) --}}
                @if ($csvParsed && !empty($csvHeaders))
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Column Mapping</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>CSV Column</th>
                                        <th>Maps To</th>
                                        <th>Sample Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($csvHeaders as $header)
                                        <tr>
                                            <td class="fw-medium">{{ $header }}</td>
                                            <td>
                                                <span class="badge bg-{{ isset($columnMapping[$header]) ? 'success' : 'secondary' }}">
                                                    {{ $columnMapping[$header] ?? 'Not mapped' }}
                                                </span>
                                            </td>
                                            <td class="text-muted small">
                                                {{ $csvRows[0][$header] ?? '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Preview Table --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Preview
                            <small class="text-muted fw-normal">
                                ({{ $this->csvRowCount }} valid row{{ $this->csvRowCount !== 1 ? 's' : '' }})
                            </small>
                        </label>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        @foreach ($csvHeaders as $header)
                                            <th>{{ $header }}</th>
                                        @endforeach
                                        <th style="width: 60px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($csvRows as $i => $row)
                                        <tr class="{{ isset($csvErrors[$i]) ? 'table-danger' : '' }}">
                                            <td>{{ $i + 1 }}</td>
                                            @foreach ($csvHeaders as $header)
                                                <td>{{ $row[$header] ?? '' }}</td>
                                            @endforeach
                                            <td>
                                                @if (isset($csvErrors[$i]))
                                                    <span class="text-danger" title="{{ implode(', ', $csvErrors[$i]) }}">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                    </span>
                                                @else
                                                    <span class="text-success">
                                                        <i class="fas fa-check-circle"></i>
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (!empty($csvErrors))
                            <div class="alert alert-warning mt-2 mb-0 py-2 small">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                {{ count($csvErrors) }} row{{ count($csvErrors) !== 1 ? 's' : '' }} with validation errors will be skipped.
                            </div>
                        @endif
                    </div>

                    {{-- Default Role (fallback for rows without a role column) --}}
                    <div class="mb-3">
                        <label for="csv-default-role" class="form-label fw-semibold">
                            Default Role <small class="text-muted fw-normal">(fallback for rows without a role)</small>
                        </label>
                        <select
                            id="csv-default-role"
                            wire:model="role"
                            class="form-select"
                        >
                            <option value="">None</option>
                            @foreach ($availableRoles as $roleId => $roleName)
                                <option value="{{ $roleId }}">{{ $roleName }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Default Message (fallback) --}}
                    <div class="mb-4">
                        <label for="csv-default-message" class="form-label fw-semibold">
                            Default Message <small class="text-muted fw-normal">(optional, fallback)</small>
                        </label>
                        <textarea
                            id="csv-default-message"
                            wire:model.lazy="message"
                            class="form-control"
                            rows="2"
                            placeholder="Default personal message for rows without one..."
                        ></textarea>
                    </div>
                @endif

                {{-- Submit --}}
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary"
                        wire:loading.attr="disabled"
                        {{ !$csvParsed ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="submitCsv">
                            <i class="fas fa-paper-plane me-1"></i>
                            Send {{ $csvParsed ? $this->csvRowCount : '' }} Invitation{{ $this->csvRowCount !== 1 ? 's' : '' }}
                        </span>
                        <span wire:loading wire:target="submitCsv">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Sending...
                        </span>
                    </button>
                </div>
            </form>
        @endif
    @endif
</div>