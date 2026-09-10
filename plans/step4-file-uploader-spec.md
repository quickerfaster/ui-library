# Custom Multi-File Uploader for Step 4 (Documents)

## Status: Specification — Awaiting Review

---

## 1. Architecture Overview

### 1.1 Problem Summary

[`Step4Documents`](app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step4Documents.php) calls [`DocumentEngine::upload()`](src/Services/Documents/DocumentEngine.php) which internally calls `$file->store()` on a Livewire `TemporaryUploadedFile`. During the Livewire dehydrate/hydrate cycle, the temporary file path is sometimes lost or the file becomes inaccessible, causing silent upload failures.

### 1.2 Solution Strategy

Replace `Step4Documents` with a self-contained component that:

- Uses `Livewire\WithFileUploAds` directly
- Stores files via Laravel's `Storage` facade (same disk as DocumentEngine: `public`)
- Creates records directly via [`QuickerFaster\UILibrary\Models\Document`](src/Models/Document.php)::create()
- **Zero library modifications** -- all code in `app/Modules/Hr/`
- Uses `hydrate` + `dehydrate` to maintain state across Livewire lifecycle

### 1.3 Component Lifecycle (Wizard Integration)

The wizard blade at [`wizard.blade.php`](app/Modules/Hr/Resources/views/onboarding/wizard.blade.php:199) renders step components with a dynamic `key`:

```blade
@livewire($componentName, $params, key('step-' . $currentStep . '-' . ($employeeId ?? 'new')))
```

**Consequence**: When navigating away from Step 4 and back, `mount()` is called fresh -- the component does NOT persist in memory. Documents survive only in the database. This is actually the correct behavior; the component re-queries the database on re-mount.

---

## 2. Component: `Step4Documents` (Replaced)

**File**: [`app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step4Documents.php`](app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step4Documents.php)

Replace the ENTIRE contents of this file.

### 2.1 Full Component Class

```php
<?php

namespace App\Modules\Hr\Http\Livewire\Onboarding\Steps;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Modules\Hr\Models\Employee;
use QuickerFaster\UILibrary\Models\Document;

/**
 * Onboarding Step 4: Documents (OPTIONAL, skippable).
 *
 * Self-contained multi-file uploader that bypasses DocumentEngine.
 * Uploads are immediate -- each file is persisted to disk and a
 * polymorphic Document record is created on every upload action.
 *
 * State persistence: Documents are stored in the database and
 * re-queried on mount(). The wizard blade destroys/recreates the
 * component on step navigation, so mount() always loads fresh.
 * Within a single step session, $uploadedDocuments is maintained
 * as a Livewire-tracked array.
 */
class Step4Documents extends Component
{
    use WithFileUploads;

    /* ---------------------------------------------------------------
       Livewire-tracked properties
       --------------------------------------------------------------- */

    /** @var \Livewire\TemporaryUploadedFile|null Single file input */
    public $document_file = null;

    public string $document_title = '';

    public string $document_type = 'identification';

    /**
     * @var array<int, array{
     *   id: int, name: string, file_name: string,
     *   document_type: string, size: int, mime_type: string,
     *   created_at: string, is_image: bool, url: string
     * }>
     */
    public array $uploadedDocuments = [];

    /* ---------------------------------------------------------------
       Constants -- public constants are accessible from Blade
       --------------------------------------------------------------- */

    /** Max number of files a user can upload */
    public const MAX_FILES = 10;

    private const MAX_FILE_SIZE_KB = 10240;   // 10 MB
    private const ALLOWED_MIMES    = 'pdf,jpg,jpeg,png,doc,docx';
    private const STORAGE_DISK     = 'public';
    private const STORAGE_PATH     = 'employee-documents';

    /* ---------------------------------------------------------------
       Lifecycle
       --------------------------------------------------------------- */

    public function mount(): void
    {
        $this->reloadDocuments();
    }

    /**
     * After every Livewire hydration (e.g. after an action completes),
     * ensure the documents array reflects the latest DB state.
     */
    public function hydrate(): void
    {
        $this->reloadDocuments();
    }

    /* ---------------------------------------------------------------
       Database sync
       --------------------------------------------------------------- */

    /**
     * Reload the uploaded documents list from the database.
     *
     * Queries the polymorphic documents table for records where:
     *   documentable_type = Employee::class
     *   documentable_id   = authenticated user's employee.id
     *
     * Uses withoutCompanyScope() because onboarding happens before
     * a company context is established.
     */
    private function reloadDocuments(): void
    {
        $employee = Employee::withoutCompanyScope()
            ->where('user_id', Auth::id())
            ->first();

        if (! $employee) {
            $this->uploadedDocuments = [];
            return;
        }

        $this->uploadedDocuments = Document::where('documentable_type', Employee::class)
            ->where('documentable_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Document $doc) => $this->mapDocumentToArray($doc))
            ->toArray();
    }

    /**
     * Transform a Document model into a frontend-friendly array.
     */
    private function mapDocumentToArray(Document $doc): array
    {
        $isImage = str_starts_with($doc->mime_type ?? '', 'image/');

        return [
            'id'            => $doc->id,
            'name'          => $doc->name,
            'file_name'     => $doc->file_name,
            'document_type' => $doc->document_type,
            'size'          => $doc->size,
            'mime_type'     => $doc->mime_type,
            'created_at'    => $doc->created_at->toDateTimeString(),
            'is_image'      => $isImage,
            'url'           => $doc->getUrl(),
        ];
    }

    /* ---------------------------------------------------------------
       Validation
       --------------------------------------------------------------- */

    public function rules(): array
    {
        return [
            'document_file'  => [
                'required',
                'file',
                'max:' . self::MAX_FILE_SIZE_KB,
                'mimes:' . self::ALLOWED_MIMES,
            ],
            'document_title' => 'required|string|max:255',
            'document_type'  => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'document_file.max'       => 'File must not exceed 10 MB.',
            'document_file.mimes'      => 'Only PDF, JPG, PNG, DOC, and DOCX files are allowed.',
            'document_title.required' => 'Please provide a title for the document.',
            'document_type.required'  => 'Please select a document type.',
        ];
    }

    /* ---------------------------------------------------------------
       Actions
       --------------------------------------------------------------- */

    /**
     * Upload a single file.
     *
     * 1. Validate the input
     * 2. Check file count limit
     * 3. Resolve the employee
     * 4. Store the file on the configured disk
     * 5. Create a Document record (polymorphic)
     * 6. Set employee_id via forceFill (HR ALTER column)
     * 7. Reload the document list
     * 8. Reset form fields
     */
    public function upload(): void
    {
        $this->validate();

        $employee = Employee::withoutCompanyScope()
            ->where('user_id', Auth::id())
            ->first();

        if (! $employee) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => __('Employee record not found. Please complete Step 1 first.'),
            ]);
            return;
        }

        // Enforce max file count
        $currentCount = Document::where('documentable_type', Employee::class)
            ->where('documentable_id', $employee->id)
            ->count();

        if ($currentCount >= self::MAX_FILES) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => __('You can upload a maximum of :count documents.', [
                    'count' => self::MAX_FILES,
                ]),
            ]);
            $this->reset(['document_file', 'document_title', 'document_type']);
            return;
        }

        /** @var \Livewire\TemporaryUploadedFile $file */
        $file = $this->document_file;

        try {
            // Store file using Laravel's Storage facade.
            // storeAs() gives us control over the filename to
            // avoid Livewire temporary path issues.
            $originalName = $file->getClientOriginalName();
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $storedPath = $file->storeAs(
                self::STORAGE_PATH,
                $safeName,
                self::STORAGE_DISK
            );

            // Create polymorphic document record.
            // We bypass DocumentEngine entirely -- direct Eloquent create.
            $document = Document::create([
                'documentable_type' => Employee::class,
                'documentable_id'   => $employee->id,
                'name'              => $this->document_title ?: $originalName,
                'file_path'         => $storedPath,
                'file_name'         => $originalName,
                'mime_type'         => $file->getMimeType(),
                'size'              => $file->getSize(),
                'document_type'     => $this->document_type,
                'disk'              => self::STORAGE_DISK,
            ]);

            // Set HR ALTER column (not in library's $fillable)
            $document->forceFill([
                'employee_id' => $employee->id,
            ]);
            $document->save();

            $this->reloadDocuments();

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => __(':name uploaded successfully.', [
                    'name' => $originalName,
                ]),
            ]);
        } catch (\Exception $e) {
            // Clean up stored file if DB insert failed
            if (isset($storedPath) && Storage::disk(self::STORAGE_DISK)->exists($storedPath)) {
                Storage::disk(self::STORAGE_DISK)->delete($storedPath);
            }

            \Log::error('Step4Documents upload failed', [
                'error'     => $e->getMessage(),
                'employee'  => $employee->id,
                'file'      => $file->getClientOriginalName(),
            ]);

            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => __('Upload failed: :message', [
                    'message' => $e->getMessage(),
                ]),
            ]);
        }

        // Reset form fields for next upload
        $this->reset(['document_file', 'document_title', 'document_type']);
    }

    /**
     * Remove (soft-delete) a document.
     *
     * Soft-delete keeps the record with deleted_at set so it is
     * recoverable. The file remains on disk until forceDelete()
     * is called (handled by Document model's forceDeleting hook).
     */
    public function remove(int $documentId): void
    {
        $document = Document::find($documentId);

        if (! $document) {
            return;
        }

        try {
            $document->delete(); // Soft delete

            $this->reloadDocuments();

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => __('Document removed.'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Step4Documents remove failed', [
                'error'       => $e->getMessage(),
                'document_id' => $documentId,
            ]);

            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => __('Failed to remove document.'),
            ]);
        }
    }

    /**
     * Wire up the final save (step completion).
     *
     * Step 4 is optional so any call to "continue" marks it complete
     * even with zero documents. The wizard tracks completion via
     * the 'documents' completedSteps key.
     */
    public function save(): void
    {
        $this->dispatch('stepComplete', step: 4);
    }

    /**
     * Skip this step.
     */
    public function skip(): void
    {
        $this->dispatch('skipStep');
    }

    /* ---------------------------------------------------------------
       Render
       --------------------------------------------------------------- */

    public function render()
    {
        return view('hr::onboarding.steps.step4-documents');
    }
}
```

### 2.2 Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| `storeAs()` with `time()` prefix | Avoids filename collisions, avoids Livewire temp path issues |
| `hydrate` reloads from DB | Ensures the component always shows the latest state after any Livewire action cycle |
| `remove()` uses soft delete | Document model has `SoftDeletes` trait; file remains on disk (safe); `forceDeleting` hook handles cleanup |
| No `engine` property | Zero DocumentEngine dependency; direct `Storage` + `Document::create()` |
| `employee_id` via `forceFill` | HR ALTER column, not in library's `$fillable` |
| `mapDocumentToArray()` | Converts models to plain arrays for Livewire serialization |
| Error cleanup deletes stored file | Prevents orphaned files on disk when DB insert fails |

---

## 3. Blade View: `step4-documents.blade.php`

**File**: [`app/Modules/Hr/Resources/views/onboarding/steps/step4-documents.blade.php`](app/Modules/Hr/Resources/views/onboarding/steps/step4-documents.blade.php)

Replace ENTIRE contents with:

```blade
{{-- Onboarding Step 4: Documents (OPTIONAL) --}}
<div>
    <h4 class="mb-1">Documents</h4>
    <p class="text-muted small mb-4">
        Upload identification, certificates, or other documents.
        You can upload multiple files
        (max {{ \App\Modules\Hr\Http\Livewire\Onboarding\Steps\Step4Documents::MAX_FILES }}).
    </p>

    {{-- =================================================== --}}
    {{-- Drop Zone / Click-to-Upload Area                      --}}
    {{-- =================================================== --}}
    <div class="upload-zone mb-4"
         x-data="{ dragOver: false }"
         x-on:dragover.prevent="dragOver = true"
         x-on:dragleave.prevent="dragOver = false"
         x-on:drop.prevent="
             dragOver = false;
             $refs.fileInput.files = $event.dataTransfer.files;
             $refs.fileInput.dispatchEvent(new Event('change'));
         "
         :class="dragOver ? 'border-primary bg-light' : ''"
         style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 2rem;
                text-align: center; cursor: pointer; transition: all 0.2s;">

        <input type="file"
               id="document_file"
               wire:model="document_file"
               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
               x-ref="fileInput"
               style="position: absolute; opacity: 0; width: 0; height: 0;">

        <label for="document_file" style="cursor: pointer; display: block; margin: 0;">
            <div style="font-size: 2rem; color: #adb5bd; margin-bottom: 0.5rem;">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <p class="mb-1 fw-semibold">
                Drag & drop a file here, or <span class="text-primary">browse</span>
            </p>
            <p class="text-muted small mb-0">
                Max 10 MB per file. Accepted: PDF, JPG, PNG, DOC, DOCX
            </p>
        </label>
    </div>

    {{-- File selected indicator (before upload button) --}}
    @if ($document_file)
        <div class="alert alert-info d-flex align-items-center mb-3">
            <i class="fas fa-file me-2"></i>
            <span class="flex-grow-1">{{ $document_file->getClientOriginalName() }}</span>
            <button type="button" class="btn-close"
                    wire:click="$set('document_file', null)"></button>
        </div>
    @endif

    {{-- =================================================== --}}
    {{-- Upload Form Fields                                    --}}
    {{-- =================================================== --}}
    @if ($document_file)
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="document_title" class="form-label">
                    Document Title <span class="text-danger">*</span>
                </label>
                <input type="text"
                       id="document_title"
                       class="form-control @error('document_title') is-invalid @enderror"
                       wire:model="document_title"
                       placeholder="e.g. National ID Card">
                @error('document_title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="document_type" class="form-label">
                    Document Type <span class="text-danger">*</span>
                </label>
                <select id="document_type"
                        class="form-select @error('document_type') is-invalid @enderror"
                        wire:model="document_type">
                    <option value="">Select type...</option>
                    <option value="identification">Identification</option>
                    <option value="certificate">Certificate</option>
                    <option value="contract">Employment Contract</option>
                    <option value="cv">CV / Resume</option>
                    <option value="other">Other</option>
                </select>
                @error('document_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Upload Button + Progress --}}
        <div class="d-flex align-items-center mb-3">
            <button type="button"
                    class="btn btn-primary"
                    wire:click="upload"
                    wire:loading.attr="disabled">
                <i class="fas fa-upload me-2"></i>
                <span wire:loading.remove wire:target="upload">Upload Document</span>
                <span wire:loading wire:target="upload">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Uploading...
                </span>
            </button>

            <div wire:loading wire:target="upload" class="ms-3 flex-grow-1">
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- =================================================== --}}
    {{-- Uploaded Documents List                               --}}
    {{-- =================================================== --}}
    @if (count($uploadedDocuments) > 0)
        <h6 class="text-secondary mb-3">
            Uploaded Documents
            <span class="badge bg-primary rounded-pill ms-2">
                {{ count($uploadedDocuments) }}
            </span>
        </h6>

        <div class="list-group list-group-flush mb-4">
            @foreach ($uploadedDocuments as $doc)
                <div class="list-group-item d-flex align-items-center px-0 py-3
                            border-bottom">
                    {{-- Thumbnail / Icon --}}
                    <div class="flex-shrink-0 me-3"
                         style="width: 48px; height: 48px;">
                        @if ($doc['is_image'])
                            <img src="{{ $doc['url'] }}"
                                 alt="{{ $doc['name'] }} thumbnail"
                                 style="width: 48px; height: 48px; object-fit: cover;
                                        border-radius: 6px;"
                                 onerror="this.style.display='none';
                                          this.nextElementSibling.style.display='flex';">
                            <div style="width: 48px; height: 48px; display: none;
                                        align-items: center; justify-content: center;
                                        background: #f8f9fa; border-radius: 6px;">
                                <i class="fas fa-file-image text-success fa-lg"></i>
                            </div>
                        @else
                            @php
                                $mime = $doc['mime_type'] ?? '';
                                $iconCls = 'fa-file-alt';
                                $iconColor = 'text-secondary';
                                if ($mime === 'application/pdf') {
                                    $iconCls = 'fa-file-pdf';
                                    $iconColor = 'text-danger';
                                } elseif (str_contains($mime, 'word')) {
                                    $iconCls = 'fa-file-word';
                                    $iconColor = 'text-primary';
                                }
                            @endphp
                            <div style="width: 48px; height: 48px; display: flex;
                                        align-items: center; justify-content: center;
                                        background: #f8f9fa; border-radius: 6px;">
                                <i class="fas {{ $iconCls }} {{ $iconColor }} fa-lg"></i>
                            </div>
                        @endif
                    </div>

                    {{-- File Info --}}
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex align-items-center">
                            <strong class="text-truncate">{{ $doc['name'] }}</strong>
                            <span class="badge bg-light text-dark ms-2 flex-shrink-0">
                                @php
                                    $typeLabels = [
                                        'identification' => 'ID',
                                        'certificate'    => 'Certificate',
                                        'contract'       => 'Contract',
                                        'cv'             => 'CV',
                                        'other'          => 'Other',
                                    ];
                                @endphp
                                {{ $typeLabels[$doc['document_type']] ?? $doc['document_type'] }}
                            </span>
                        </div>
                        <div class="text-muted small">
                            {{ number_format($doc['size'] / 1024, 0) }} KB &middot;
                            {{ \Carbon\Carbon::parse($doc['created_at'])->diffForHumans() }}
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex-shrink-0 ms-3">
                        <a href="{{ $doc['url'] }}"
                           target="_blank"
                           class="btn btn-sm btn-outline-secondary me-1"
                           title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                title="Remove"
                                wire:click="remove({{ $doc['id'] }})"
                                wire:confirm="Remove this document?">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-4 text-muted">
            <i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block"></i>
            <p class="mb-0">No documents uploaded yet.</p>
        </div>
    @endif

    <hr>

    {{-- =================================================== --}}
    {{-- Navigation Actions                                    --}}
    {{-- =================================================== --}}
    <div class="d-flex justify-content-between mt-3">
        <button type="button" class="btn btn-outline-secondary" wire:click="skip">
            Skip for Now
        </button>
        <button type="button" class="btn btn-primary" wire:click="save">
            Continue
            <i class="fas fa-arrow-right ms-2"></i>
        </button>
    </div>
</div>
```

### 3.1 UX Features Implemented

| Feature | Implementation |
|---------|---------------|
| Drag-and-drop zone | Alpine.js `x-on:drop` + hidden `<input type="file">` |
| Click-to-browse | Label wraps entire zone, triggers hidden input |
| File selected feedback | Alert box with filename + dismiss button |
| Upload progress | Livewire `wire:loading` spinner + progress bar |
| Image thumbnail | `<img>` with `onerror` fallback to icon |
| PDF/DOC icon fallback | Font Awesome icons keyed by MIME type |
| Remove confirmation | Livewire `wire:confirm` attribute |
| Empty state | Centered cloud-upload with "No documents yet" |
| Document count badge | Badge next to "Uploaded Documents" header |

---

## 4. Wizard Integration

### 4.1 Step Completion Detection

The [`EmployeeOnboardingWizard`](app/Modules/Hr/Http/Livewire/Onboarding/EmployeeOnboardingWizard.php) already listens:

**Wizard blade** (lines 243-249):
```javascript
Livewire.on('stepComplete', (event) => {
    @this.call('onStepComplete', event.step);
    setTimeout(() => { @this.call('nextStep'); }, 400);
});
```

**Wizard class** (lines 171-178):
```php
public function onStepComplete(int $step): void
{
    $steps = $this->steps;
    $index = $step - 1;
    if (isset($steps[$index])) {
        $this->completedSteps[$steps[$index]['key']] = true;
    }
}
```

The new `Step4Documents::save()` dispatches `stepComplete` with `step: 4`, which triggers `onStepComplete(4)`, setting `completedSteps['documents'] = true`.

**No changes needed.**

### 4.2 Initial Completion Detection (Wizard Mount)

The wizard's `mount()` already checks for existing documents via the polymorphic `documents()` relationship (lines 80-96). Our new component creates records with the same polymorphic fields (`documentable_type = Employee::class`, `documentable_id = employee.id`), so the existing detection works without modification.

**No changes needed.**

### 4.3 Step Component Key

The wizard blade renders with a dynamic key that changes when `$currentStep` changes, causing mount() to run fresh. Our `mount()` does `reloadDocuments()` which queries the DB. Correct behavior.

**No changes needed.**

---

## 5. Service Provider Changes

**File**: [`app/Modules/Hr/Providers/HrsServiceProvider.php`](app/Modules/Hr/Providers/HrsServiceProvider.php)

The component alias is already registered at line 69:
```php
Livewire::component('qf.onboarding.step4-documents', \App\Modules\Hr\Http\Livewire\Onboarding\Steps\Step4Documents::class);
```

Since we're replacing the class at the same FQCN, **no provider changes needed**.

---

## 6. Configuration

**File**: [`app/Modules/Hr/Config/onboarding.php`](app/Modules/Hr/Config/onboarding.php)

No changes needed. Step definition at lines 51-60 references `component => 'qf.onboarding.step4-documents'` which still maps to our replaced class.

---

## 7. Removed Dependencies

The new `Step4Documents` no longer imports or uses:
- `QuickerFaster\\UILibrary\\Services\\Documents\\DocumentEngine` ❌ REMOVED
- `$this->engine` property ❌ REMOVED
- `boot(DocumentEngine $engine)` method ❌ REMOVED

---

## 8. Edge Cases & Error Handling

### 8.1 Employee Not Found
On upload attempt, shows: "Employee record not found. Please complete Step 1 first."

### 8.2 Max File Count Reached
On upload attempt, shows error with count. Form fields reset so user can remove existing files first.

### 8.3 File Storage Failure
Exception caught, logged, error shown. Form fields NOT reset (user can retry).

### 8.4 DB Insert Failure After File Stored
Stored file cleaned up via `Storage::disk()->delete()`. Error logged and shown.

### 8.5 Remove Non-Existent Document
Returns silently. No error.

### 8.6 Large Files
Validation `max:` rule enforced (10 MB). Also requires `post_max_size` and `upload_max_filesize` >= 10 MB in php.ini.

### 8.7 Concurrent Uploads
Single-file input. No race conditions.

---

## 9. Summary of Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step4Documents.php` | **REPLACE** | New self-contained uploader with direct Storage + Document::create() |
| `app/Modules/Hr/Resources/views/onboarding/steps/step4-documents.blade.php` | **REPLACE** | New blade with drag-drop zone, file list, thumbnails, remove |
| `app/Modules/Hr/Providers/HrsServiceProvider.php` | **NO CHANGE** | Same FQCN, same alias |
| `app/Modules/Hr/Http/Livewire/Onboarding/EmployeeOnboardingWizard.php` | **NO CHANGE** | Existing event listeners and mount detection remain compatible |
| `app/Modules/Hr/Config/onboarding.php` | **NO CHANGE** | Same component reference, same step key |
| `src/Models/Document.php` (library) | **NO CHANGE** | Used directly via Eloquent, no modifications |
| `src/Services/Documents/DocumentEngine.php` (library) | **NO CHANGE** | Bypassed, not referenced |

**Total files modified: 2** (component class + blade view)

---

## 10. Testing Checklist

When implementing, verify:

- [ ] Upload a PDF → appears in list with red PDF icon
- [ ] Upload a JPG → appears with thumbnail preview
- [ ] Upload a DOCX → appears with blue Word icon
- [ ] Remove a document → disappears from list, soft-deleted
- [ ] Navigate to Step 3, then back to Step 4 → documents still visible (DB re-query)
- [ ] Try uploading >10 files → error message
- [ ] Try uploading file >10 MB → validation error
- [ ] Click "Continue" with zero documents → step marked complete (optional)
- [ ] Click "Skip for Now" → wizard advances, step marked skipped
- [ ] After upload + complete → wizard shows Step 4 as "Complete" in sidebar
- [ ] On re-mount of wizard → documents count detected, step pre-marked complete