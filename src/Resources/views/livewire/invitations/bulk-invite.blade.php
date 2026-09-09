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
        {{-- Form State --}}
        <form wire:submit.prevent="submit">
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
                    <span wire:loading.remove wire:target="submit">
                        <i class="fas fa-paper-plane me-1"></i>
                        Send {{ $this->emailCount > 0 ? $this->emailCount : '' }} Invitation{{ $this->emailCount !== 1 ? 's' : '' }}
                    </span>
                    <span wire:loading wire:target="submit">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Sending...
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>