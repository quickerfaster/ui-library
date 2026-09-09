# AI Prompt: Fix Step 4 Document Upload in Onboarding Wizard

> **Purpose**: Paste this entire document into a new AI session to fix the remaining Step 4 document upload issue.
> **Mode**: Start in `architect` mode to analyze the problem, then switch to `code` mode to implement.
> **Status**: This is the last remaining integration issue after 49 bugs were fixed across Phases 1-8.

---

## Context

### What Was Built

An **invitation system + employee onboarding wizard** spanning 8 implementation phases:

| Phase | Scope | Status |
|-------|-------|--------|
| 1-4 | Library infrastructure (Invitable contract, InvitationService, accept flow, email, DataTable) | Complete |
| 5 | HR entry points (Onboarding context group, /hr/invitations, overview dashboard) | Complete |
| 6 | Deep HR integration (employee selector, profile tab, send-on-create checkbox, needs-linking filter) | Complete |
| 7 | Post-acceptance onboarding wizard (5 consolidated steps: Employee Record, Profile, Payroll, Documents, Notifications) | Complete |
| 8 | Competitive features (auto-reminders, analytics, CSV bulk invite, audit log, department scoping) | Complete |

### Current State

- **49 bugs** were found and fixed during integration testing across all phases
- **Core flow works**: invitation accept, onboarding wizard steps 1, 2, 3, 5 all functional
- **One remaining issue**: Step 4 (Document Upload) grays out and does nothing. Uploaded documents do not persist across back/next navigation.

### The Document Architecture Change

The document upload was recently rewritten to use the library's `DocumentEngine` instead of a custom file handler. The `Employee` model now implements both `Invitable` and `Documentable` contracts, and uses the `HasDocuments` trait. This rewrite introduced conflicts with the pre-existing HR module `Document` model.

---

## The Problem

### Symptom 1: Upload grays out / does nothing

When a user reaches Step 4 (Documents) in the onboarding wizard, the file upload UI appears but either:
- Clicking "Upload Document" grays out the button and nothing happens
- Or the form silently fails without any error message or notification

### Symptom 2: Documents don't persist across navigation

If a document is uploaded on Step 4, navigating back to a previous step (Step 3) and then forward to Step 4 again shows an empty document list -- the uploaded document is lost.

### Root Causes (6 Identified)

#### Cause 1: HR `Document` model name collision

The HR module has its own legacy `Document` model at namespace `App\Modules\Hr\Models\Document` that also maps to the `documents` table (line 26: `protected $table = 'documents';`). The library's `Document` model at `QuickerFaster\UILibrary\Models\Document` is the canonical model used by `DocumentEngine`.

The `HasDocuments` trait explicitly uses `QuickerFaster\UILibrary\Models\Document::class` in its `documents()` MorphMany relationship. But `Employee.php` (line 20) imports `use App\Modules\Hr\Models\Document;` -- the legacy HR model. This import could shadow the trait's Document reference, causing the MorphMany to point at the wrong model.

#### Cause 2: HR `Document` boot hook interferes with `DocumentEngine`

The HR `Document` model has a `creating` boot hook (lines 77-92) that:

1. Copies `employee_id` to `documentable_type`/`documentable_id` when the latter is empty
2. Copies the `document` field to `file_path` and `file_name` when the latter is empty
3. Sets `uploaded_at` to `now()` if not set

When `DocumentEngine::upload()` calls `Document::create()`, which `Document` model receives the call depends on which model is resolved. If the HR `Document` gets the call, its boot hook may transform the data in ways that conflict with what `DocumentEngine` expects. The `DocumentEngine` already sets `documentable_type`, `documentable_id`, `file_path`, `file_name`, `mime_type`, and `size` -- the HR model's hook may overwrite or corrupt these values.

#### Cause 3: `$document_type` field ignored

The `step4-documents.blade.php` view collects `document_type` (identification, certificate, contract, cv, other) via a select dropdown and validates it as required. But `Step4Documents::upload()` (lines 103-108) never passes this value to `DocumentEngine::upload()`. The engine sets `document_type` from `$entity->getDocumentType()` which returns the hardcoded string `'employee_documents'` -- not the user-selected category.

The `DocumentEngine::upload()` signature accepts only `(Documentable $entity, UploadedFile $file, ?string $name = null)` -- no parameter exists to pass a custom `document_type`. The file metadata is lost.

#### Cause 4: Livewire `TemporaryUploadedFile` vs `UploadedFile`

`Step4Documents` uses Livewire's `WithFileUploads` trait, which stores files as `Livewire\TemporaryUploadedFile` objects. The `DocumentEngine::upload()` type-hints `Illuminate\Http\UploadedFile`. While `TemporaryUploadedFile` extends `UploadedFile`, the Livewire lifecycle might not properly hydrate the file before the upload method is called, especially during back-navigation when the component is dehydrated and re-hydrated.

#### Cause 5: `hydrate()` re-query loses state

`Step4Documents::hydrate()` (lines 48-51) calls `reloadDocuments()` on every Livewire hydration cycle. During back-navigation, the component is dehydrated (serialized) and then re-hydrated (unserialized). If the file was uploaded but `reloadDocuments()` queries the database before the file is fully persisted by the previous request, it returns an empty list -- making it appear that the document was never uploaded.

#### Cause 6: Wizard `mount()` check always returns true

`EmployeeOnboardingWizard.php` (lines 80-84):

```php
if ($this->employee && method_exists($this->employee, 'documents')) {
    if ($this->employee->documents()->count() > 0) {
        $this->completedSteps['documents'] = true;
    }
}
```

`method_exists()` returns true because `HasDocuments` provides `documents()`. But if the relationship resolves to the wrong `Document` model (due to Cause 1), `count()` returns 0 even when documents exist, making Step 4 appear incomplete on every page load. Also, `method_exists()` would return true even if the method existed but was broken -- a try/catch or explicit exists check would be more robust.

---

## Files to Reference

### Consuming App Files (in the HR Laravel project at `/Users/mac/Projects/LaravelProjects/hr-consuming-app/`)

| File | Path | Role |
|------|------|------|
| Employee model | `app/Modules/Hr/Models/Employee.php` | Implements Documentable + Invitable, uses HasDocuments, imports legacy Document (line 20) |
| HR Document model (legacy) | `app/Modules/Hr/Models/Document.php` | Maps to `documents` table, has boot hook that transforms fields |
| Step4 document component | `app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step4Documents.php` | The Livewire component for Step 4 |
| Step4 blade view | `app/Modules/Hr/Resources/views/onboarding/steps/step4-documents.blade.php` | The view with file input and document list |
| Onboarding wizard | `app/Modules/Hr/Http/Livewire/Onboarding/EmployeeOnboardingWizard.php` | The parent wizard component that orchestrates all steps |
| Other step components | `app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step1EmployeeRecord.php`, `Step2EmployeeProfile.php`, `Step3PayrollBanking.php`, `Step5NotificationPreferences.php` | Reference for how other steps handle state |

### Library Files (in the UI library at `/Users/mac/Projects/Libraries/ui-library/`)

| File | Path | Role |
|------|------|------|
| Documentable contract | `src/Contracts/Documents/Documentable.php` | Interface with 4 methods: getDocumentableId(), getDocumentType(), getDocumentStoragePath(), getDocumentTemplateData() |
| HasDocuments trait | `src/Traits/Documents/HasDocuments.php` | Provides documents() MorphMany, uploadDocument(), getDocuments(), deleteDocument() |
| DocumentEngine | `src/Services/Documents/DocumentEngine.php` | Handles upload(), generatePdf(), generateExcel(), getDocuments(), delete() |
| Document model (library) | `src/Models/Document.php` | The polymorphic Document model with documentable_type/documentable_id |
| Invitable contract | `src/Contracts/Invitations/Invitable.php` | Interface with getInvitableType(), getInvitableId() |

---

## Architecture Rules (Non-Negotiable)

### 1. Library Must Remain Decoupled from Consuming App

From `docs/library/25-library-independence-safeguards.md`:
- The library **MUST NOT** reference any `App\Modules\*` namespace
- All library interactions with consuming-app code go through **contracts** (interfaces in `src/Contracts/`)
- The library provides contracts; the consuming app implements them
- Test all library code with the "two-domain test": would this work for both HR and inventory?

### 2. Consuming App Modules Must Be Self-Contained

From `docs/consuming-app/pre-coding-checklist.md`:
- All module files under `app/Modules/{ModuleName}/`
- Models in `Models/`, blade views in `Resources/views/`, Livewire components in `Http/Livewire/`
- Livewire views in `Resources/views/livewire/` (not root `views/`)
- Every Livewire component must be registered in the module's service provider

### 3. One Owner Per Database Table

From `docs/library/27-architecture-boundary.md`:
- **One owner per table.** The library owns the `documents` table (via migration `Database/Migrations/2026_08_08_000002_create_documents_table.php`). Modules must not create the same table.
- Modules ALTER library tables for domain columns; they do not compete for table ownership.
- The HR `Document` model maps to the same `documents` table as the library's `Document` -- this violates the one-owner principle.

### 4. Follow Existing Patterns

- **Contract pattern**: Define interface in library, implement in consuming app, engine uses contract
- **Subclass pattern**: Library provides base class, consuming app extends with domain logic, registered in service provider
- **Config-driven**: Domain-specific values flow through config arrays, never hardcoded references

---

## Key Documentation References

| Document | Path | Content |
|----------|------|---------|
| Documentable contract usage | `docs/consuming-app/contracts.md` section 3 | How to implement Documentable, how DocumentEngine works, how to use HasDocuments trait |
| Pre-coding checklist | `docs/consuming-app/pre-coding-checklist.md` | Rules for creating blade views, Livewire components, modifying library code, adding files to modules |
| Library independence safeguards | `docs/library/25-library-independence-safeguards.md` | Hard rules: contract boundary, config-driven design, two-domain test, grep gates |
| Architecture boundary | `docs/library/27-architecture-boundary.md` section 5 | Document management verdict: library owns mechanism, modules own business metadata. Modules ALTER, never create competing tables. |
| Consolidated roadmap | `plans/invitation-onboarding-consolidated-roadmap.md` | Full 8-phase roadmap, bug fix catalog, remaining open issues |

---

## Suggested Fix Strategy

### Step 1: Resolve the Document Model Collision

The HR `Document` model must not compete with the library's `Document` for table ownership.

**Options:**

- **Option A (Recommended):** Rename the HR `Document` model to `EmployeeDocument` or similar, and if the HR model has its own data requirements, change its `$table` to a different name (e.g., `hr_employee_documents`). Remove the legacy `document` field mapping boot hooks since `DocumentEngine` handles all that. This follows the architecture rule that the library owns the `documents` table.
- **Option B:** Remove the HR `Document` model entirely if it's no longer used by other parts of the HR module. The `DocumentEngine` and `HasDocuments` trait already handle all document management. The HR model's boot hooks could be reimplemented as an event listener if the bridging logic is still needed for legacy data.
- **Option C:** Keep HR `Document` as a subclass of the library `Document`, overriding only the `$table` property. However, this is risky because Laravel's MorphMany relationship stores the base class FQCN in `documentable_type`, so the polymorphic lookup might break.

**Recommended approach: Option A or B.** Also remove the unused `use App\Modules\Hr\Models\Document;` import from `Employee.php` (line 20) since the `HasDocuments` trait already imports the library's `Document`.

### Step 2: Fix the `$document_type` Metadata Gap

The `document_type` selected by the user (identification, certificate, contract, etc.) must be persisted. Currently `DocumentEngine` sets it from `$entity->getDocumentType()` which returns `'employee_documents'` -- a generic value.

**Options:**
1. Store the user-selected type in the Document's `metadata` JSON column. The library `Document` model already has a `metadata` cast to array. Modify `Step4Documents::upload()` to pass the type as custom metadata after the engine creates the document.
2. Override `getDocumentType()` on Employee to return a dynamic value -- but this is messy since the method takes no parameters.
3. If the `document_type` column should reflect the user's choice, update it on the Document after `DocumentEngine::upload()` returns.

### Step 3: Fix Livewire File Upload Lifecycle

Ensure that:
- `TemporaryUploadedFile` is properly handled before passing to `DocumentEngine`
- The upload completes before `reloadDocuments()` is called
- `hydrate()` doesn't wipe out in-progress uploads -- consider using a flag like `$recentlyUploaded` to skip the reload after a successful upload
- The file validation rules are correct for Livewire's temporary upload system

### Step 4: Fix Wizard `mount()` State Detection

The `method_exists($this->employee, 'documents')` check in `EmployeeOnboardingWizard::mount()` always returns true, but the relationship may resolve through the wrong model. After fixing the model collision (Step 1), this should work correctly. Also consider using `$this->employee->getDocuments()` (via the trait) instead of the raw `documents()->count()` relationship, and wrap it in a try/catch to handle edge cases gracefully.

### Step 5: Test the Full Flow

After fixes:
1. Invite a user, accept the invitation, enter the onboarding wizard
2. Navigate to Step 4, upload a document, verify it appears in the uploaded documents list
3. Navigate back to Step 3, then forward to Step 4 -- verify the document still appears
4. Complete the wizard -- verify documents are accessible on the employee profile
5. Test with multiple file types (PDF, JPG, PNG) and edge cases (empty file, too-large file, wrong type)

---

## Implementation Notes

- **Do not modify the library** unless absolutely necessary. All domain-specific fixes should be in `app/Modules/Hr/`.
- If a library change is needed (e.g., adding `$metadata` parameter to `DocumentEngine::upload()`), ensure it passes the two-domain test: would this metadata feature be useful for Inventory documents too?
- Run the grep gate from `25-library-independence-safeguards.md` section 4.1 after any library changes.
- Follow the checklist in `pre-coding-checklist.md` for every file created or modified.
- Register any new Livewire components in the HR module's service provider.
- After fixing, mark issues 48 and 49 as resolved in `plans/invitation-onboarding-consolidated-roadmap.md`.