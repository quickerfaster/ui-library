<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Invitations;

use Livewire\Component;
use Livewire\WithFileUploads;
use QuickerFaster\UILibrary\Services\Invitations\InvitationService;
use Spatie\Permission\Models\Role;

/**
 * Bulk Invite component.
 *
 * Allows administrators to send multiple invitations at once via:
 * 1. Pasting a list of email addresses (one per line)
 * 2. Uploading a CSV file with column mapping
 */
class BulkInvite extends Component
{
    use WithFileUploads;

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

    /** @var string Active tab: 'paste' or 'csv'. */
    public string $activeTab = 'paste';

    /** @var \Livewire\TemporaryUploadedFile|null Uploaded CSV file. */
    public $csvFile = null;

    /** @var array Parsed CSV rows for preview. */
    public array $csvRows = [];

    /** @var array CSV column headers. */
    public array $csvHeaders = [];

    /** @var array Column mapping: csv_header => field (email, role, message). */
    public array $columnMapping = [];

    /** @var array Validation errors per CSV row. */
    public array $csvErrors = [];

    /** @var bool Whether CSV has been parsed and is ready for preview. */
    public bool $csvParsed = false;

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
            'emails' => 'nullable|string',
            'role' => 'nullable|string',
            'message' => 'nullable|string|max:1000',
            'csvFile' => 'nullable|file|mimes:csv,txt|max:2048',
            'activeTab' => 'required|in:paste,csv',
        ];
    }

    /**
     * Parse the emails textarea into an array of trimmed, non-empty addresses.
     */
    protected function parseEmails(): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $this->emails));

        return array_values(array_filter(array_map('trim', $lines), function ($line) {
            return $line !== '' && filter_var($line, FILTER_VALIDATE_EMAIL);
        }));
    }

    /**
     * Switch to a different tab.
     */
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetValidation();
    }

    /**
     * Handle CSV file upload and parse it for preview.
     */
    public function updatedCsvFile(): void
    {
        $this->validateOnly('csvFile');

        if (! $this->csvFile) {
            return;
        }

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            $this->addError('csvFile', 'Unable to read the uploaded file.');
            return;
        }

        // Read headers (first row)
        $headers = fgetcsv($handle);
        if (! $headers) {
            $this->addError('csvFile', 'CSV file appears to be empty.');
            fclose($handle);
            return;
        }

        $headers = array_map('trim', $headers);
        $this->csvHeaders = $headers;

        // Auto-detect column mapping
        $this->autoDetectMapping($headers);

        // Read data rows
        $rows = [];
        $rowIndex = 0;
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (count(array_filter($row)) === 0) {
                continue;
            }

            $mapped = [];
            foreach ($headers as $i => $header) {
                $mapped[$header] = $row[$i] ?? '';
            }
            $rows[] = $mapped;
            $rowIndex++;
        }
        fclose($handle);

        $this->csvRows = $rows;
        $this->csvParsed = true;
        $this->validateCsvRows();
    }

    /**
     * Auto-detect column mapping from CSV headers.
     */
    protected function autoDetectMapping(array $headers): void
    {
        $mapping = [];
        $lowerHeaders = array_map('strtolower', $headers);

        foreach ($lowerHeaders as $i => $header) {
            if (in_array($header, ['email', 'e-mail', 'email address', 'mail'])) {
                $mapping[$headers[$i]] = 'email';
            } elseif (in_array($header, ['role', 'roles', 'role name', 'position'])) {
                $mapping[$headers[$i]] = 'role';
            } elseif (in_array($header, ['message', 'note', 'notes', 'personal message', 'custom message'])) {
                $mapping[$headers[$i]] = 'message';
            }
        }

        $this->columnMapping = $mapping;
    }

    /**
     * Validate parsed CSV rows and populate errors.
     */
    protected function validateCsvRows(): void
    {
        $this->csvErrors = [];
        $emailColumn = $this->getMappedColumn('email');

        if (! $emailColumn) {
            $this->addError('csvFile', 'Could not identify an email column. Please map columns manually.');
            return;
        }

        foreach ($this->csvRows as $i => $row) {
            $rowErrors = [];
            $email = trim($row[$emailColumn] ?? '');

            if (empty($email)) {
                $rowErrors[] = 'Email is required.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = "Invalid email: {$email}";
            }

            if (! empty($rowErrors)) {
                $this->csvErrors[$i] = $rowErrors;
            }
        }
    }

    /**
     * Get the CSV header name mapped to a given field.
     */
    protected function getMappedColumn(string $field): ?string
    {
        foreach ($this->columnMapping as $header => $mappedField) {
            if ($mappedField === $field) {
                return $header;
            }
        }

        return null;
    }

    /**
     * Get the mapped value for a field from a CSV row.
     */
    protected function getMappedValue(array $row, string $field): ?string
    {
        $column = $this->getMappedColumn($field);

        return $column ? ($row[$column] ?? null) : null;
    }

    /**
     * Submit the bulk invite from paste tab.
     */
    public function submitPaste(): void
    {
        $this->validate([
            'emails' => 'required|string',
            'role' => 'required|string',
            'message' => 'nullable|string|max:1000',
        ]);

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
     * Submit the bulk invite from CSV tab.
     */
    public function submitCsv(): void
    {
        if (empty($this->csvRows)) {
            $this->addError('csvFile', 'Please upload a CSV file first.');
            return;
        }

        if (! empty($this->csvErrors)) {
            $this->addError('csvFile', 'Please fix validation errors before submitting.');
            return;
        }

        $emailColumn = $this->getMappedColumn('email');
        if (! $emailColumn) {
            $this->addError('csvFile', 'Email column mapping is required.');
            return;
        }

        /** @var InvitationService $service */
        $service = app(InvitationService::class);

        $count = 0;
        foreach ($this->csvRows as $i => $row) {
            // Skip rows with errors
            if (isset($this->csvErrors[$i])) {
                continue;
            }

            $email = trim($row[$emailColumn] ?? '');
            if (empty($email)) {
                continue;
            }

            $role = $this->getMappedValue($row, 'role') ?: $this->role;
            $message = $this->getMappedValue($row, 'message') ?: ($this->message ?: null);

            if (empty($role)) {
                continue;
            }

            $service->create(
                email: $email,
                role: $role,
                message: $message,
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
     * Submit handler that delegates to the active tab's submit method.
     */
    public function submit(): void
    {
        if ($this->activeTab === 'csv') {
            $this->submitCsv();
        } else {
            $this->submitPaste();
        }
    }

    /**
     * Reset the form for another batch.
     */
    public function resetForm(): void
    {
        $this->reset(['emails', 'role', 'message', 'submitted', 'sentCount', 'csvFile', 'csvRows', 'csvHeaders', 'columnMapping', 'csvErrors', 'csvParsed', 'activeTab']);
        $this->activeTab = 'paste';
    }

    /**
     * Get the count of valid email addresses in the textarea.
     */
    public function getEmailCountProperty(): int
    {
        return count($this->parseEmails());
    }

    /**
     * Get the count of valid CSV rows.
     */
    public function getCsvRowCountProperty(): int
    {
        return count($this->csvRows) - count($this->csvErrors);
    }

    public function render()
    {
        return view('qf::livewire.invitations.bulk-invite');
    }
}