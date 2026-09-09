<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Invitations;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Invitations\InvitationService;
use Spatie\Permission\Models\Role;

/**
 * Bulk Invite component.
 *
 * Allows administrators to send multiple invitations at once by
 * pasting a list of email addresses (one per line).
 */
class BulkInvite extends Component
{
    /** @var string One email address per line. */
    public string $emails = '';

    /** @var string|null The role to assign to all invited users. */
    public ?string $role = null;

    /** @var string|null Optional personal message for all invitations. */
    public ?string $message = null;

    /** @var bool Whether the bulk invite was successfully submitted. */
    public bool $submitted = false;

    /** @var int Number of invitations successfully sent. */
    public int $sentCount = 0;

    /** @var array List of available roles for the dropdown. */
    public array $availableRoles = [];

    public function mount(): void
    {
        $this->availableRoles = Role::pluck('name', 'id')->toArray();
    }

    /**
     * Validation rules.
     */
    protected function rules(): array
    {
        return [
            'emails' => 'required|string',
            'role' => 'required|string',
            'message' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Parse the emails textarea into an array of trimmed, non-empty addresses.
     *
     * @return array
     */
    protected function parseEmails(): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $this->emails));

        return array_values(array_filter(array_map('trim', $lines), function ($line) {
            return $line !== '' && filter_var($line, FILTER_VALIDATE_EMAIL);
        }));
    }

    /**
     * Submit the bulk invite.
     *
     * @return void
     */
    public function submit(): void
    {
        $this->validate();

        $emails = $this->parseEmails();

        if (empty($emails)) {
            $this->addError('emails', 'Please enter at least one valid email address.');
            return;
        }

        /** @var InvitationService $service */
        $service = app(InvitationService::class);

        $count = 0;
        foreach ($emails as $email) {
            $service->create(
                email: $email,
                role: $this->role,
                message: $this->message ?: null,
                createdBy: auth()->id(),
            );
            $count++;
        }

        $this->sentCount = $count;
        $this->submitted = true;

        $this->dispatch('invitations-sent', [
            'count' => $count,
        ]);
    }

    /**
     * Reset the form for another batch.
     *
     * @return void
     */
    public function resetForm(): void
    {
        $this->reset(['emails', 'role', 'message', 'submitted', 'sentCount']);
    }

    /**
     * Get the count of valid email addresses in the textarea.
     *
     * @return int
     */
    public function getEmailCountProperty(): int
    {
        return count($this->parseEmails());
    }

    public function render()
    {
        return view('qf::livewire.invitations.bulk-invite');
    }
}