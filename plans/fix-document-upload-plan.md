# Fix Plan: Step 4 Document Upload Bug

> **Status**: Verified against all 19 referenced files. Ready for Code mode implementation.
> **Date**: 2026-09-09

\---

## 1. Root Cause Verification Summary

| Cause | Prompt Description | Verified? | Severity | Notes |
|-------|-------------------|-----------|----------|-------|
| 1 | HR Document model name collision | **CONFIRMED** | CRITICAL | The HR ALTER migration adds `employee_id` as NOT NULL. `DocumentEngine::upload()` does not set it, causing silent database constraint failure |
| 2 | HR Document boot hook interferes | **NOT A DIRECT BUG** | Low | Library uses `QuickerFaster\UILibrary\Models\Document`, not HR's `Document`. Boot hooks don't fire. But the competing model is an architectural violation |
| 3 | `$document_type` field ignored | **CONFIRMED** | High | `Step4Documents::upload()` never passes `$this->document_type`. The engine uses `$entity->getDocumentType()` which returns hardcoded `'employee_documents'` |
| 4 | `TemporaryUploadedFile` vs `UploadedFile` | **NOT A BUG** | None | `Livewire\TemporaryUploadedFile` extends `Illuminate\Http\UploadedFile` — type hint satisfied |
| 5 | `hydrate()` re-query loses state | **NOT AN INDEPENDENT CAUSE** | None | `hydrate()` correctly reloads. Empty list is a *symptom* of Cause 1 preventing uploads from succeeding |
| 6 | Wizard `method_exists` always true | **MINOR** | Low | `method_exists` correctly returns `true`. The issue is `documents()->count()` returns 0 because no documents can be uploaded (Cause 1) |

### Additional Root Causes Discovered

| # | Description | Severity | Evidence |
|---|-------------|----------|----------|
| A | HR migration `2026_08_18_000002_add_hr_columns_to_documents_table.php` adds `employee_id` as NOT NULL FK; `DocumentEngine::upload()` never sets it | **CRITICAL** | Migration line 20: `$table->foreignId('employee_id')` — no `nullable()`. DocumentEngine lines 29-39: create array excludes `employee_id` |
| B | [`onboarding.php`](hr-consuming-app:app/Modules/Hr/Config/onboarding.php:55) references `App\Modules\Hr\Models\Document::class` as the step's model | **Medium** | Onboarding config line 55 |
| C | [`employee.php` Data config](hr-consuming-app:app/Modules/Hr/Data/employee.php:396-400) maps `documents` relationship to HR `Document` model with `employee_id` FK | **Medium** | Data config lines 396-400 |
| D | HR `Document` model's `HasCompanyScope` trait adds a global scope — if any code path resolved through this model, it would filter by `company_id` (which the library model does not set) | **Low** | HR Document line 18 |

---

## 2. Implementation Plan (Execution Order)

### ⚠️ Critical Path

```
Fix 1 (DB migration) → Fix 2 (set employee_id after upload) → Fix 3 (document_type)
```

These three must be done together for upload to work. Fixes 4-8 are cleanup/consolidation.

---

### Fix 1: Make `employee_id` Nullable on `documents` Table

**Root Cause**: Additional Cause A

**Why**: The library owns the `documents` table and `DocumentEngine::upload()` creates rows via the library `Document` model, which knows nothing about `employee_id`. The HR module legitimately ALTERs the table to add its domain column, but making it NOT NULL breaks library-driven inserts.

**File**: Create new migration file
**Path**: `app/Modules/Hr/Database/Migrations/2026_09_10_000001_make_employee_id_nullable_in_documents_table.php`

**Change**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // The library's DocumentEngine creates documents without employee_id.
            // employee_id is a domain column added by the HR module; it must be
            // nullable so the library's polymorphic document engine can create
            // rows that are later enriched with HR-specific data.
            $table->unsignedBigInteger('employee_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
        });
    }
};
```

**Risk**: Breaking existing HR document rows that rely on NOT NULL. Mitigation: existing rows already have `employee_id` set (HR model's `creating` hook ensures it), so making the column nullable won't affect existing data.

---

### Fix 2: Set `employee_id` After DocumentEngine Upload in Step4Documents

**Root Cause**: Additional Cause A (companion to Fix 1)

**Why**: Even with `employee_id` nullable, we should populate it so the HR module can query documents by employee. The library engine doesn't know about `employee_id`, so the consuming app must set it post-creation.

**File**: `app/Modules/Hr/Https/Livewire/Onboarding/Steps/Step4Documents.php`

**Change** (lines 96-118):

**BEFORE**:
```php
    public function upload(): void
    {
        $this->validate();

        $employee = Employee::withoutCompanyScope()->where('user_id', Auth::id())->first();

        if ($employee && $this->document_file) {
            $this->engine->upload(
                $employee,
                $this->document_file,
                $this->document_title,
            );

            $this->reloadDocuments();

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => __('Document uploaded successfully.'),
            ]);
        }

        // Reset form fields for next upload
        $this->reset(['document_file', 'document_title', 'document_type']);
    }
```

**AFTER**:
```php
    public function upload(): void
    {
        $this->validate();

        $employee = Employee::withoutCompanyScope()->where('user_id', Auth::id())->first();

        if ($employee && $this->document_file) {
            try {
                $document = $this->engine->upload(
                    $employee,
                    $this->document_file,
                    $this->document_title,
                );

                // Populate HR domain columns that the library engine
                // does not know about. 'document_type' is in the
                // library model's $fillable; 'employee_id' is an HR
                // ALTER column and must be set via forceFill.
                $document->forceFill([
                    'employee_id' => $employee->id,
                ]);

                // Persist the user-selected document category (the engine
                // uses the entity's hardcoded getDocumentType() value).
                if (!empty($this->document_type)) {
                    $document->document_type = $this->document_type;
                }

                $document->save();

                $this->reloadDocuments();

                $this->dispatch('notify', [
                    'type'    => 'success',
                    'message' => __(/'Document uploaded successfully.'),
                ]);
            } catch (\Exception $e) {
                $this->dispatch('notify', [
                    'type'    => 'error',
                    'message' => __(/'Upload failed: ') . $e->getMessage(),
                ]);
            }
        }

        // Reset form fields for next upload
        $this->reset(['document_file', 'document_title', 'document_type']);
    }
```

**Risk**: `forceFill()` bypasses mass-assignment protection for `employee_id`. This is intentional — `employee_id` is not in the library model's `$fillable` because the library doesn't own it. The HR module owns this column and must write to it.

---

### Fix 3: Remove Unused HR Document Import from Employee Model

**Root Cause**: Cause 1 (ambiguity contributor)

**File**: `app/Modules/Hr/Models/Employee.php`

**Change** (line 20):

**BEFORE**:
```php
use App\Modules\Hr\Models\Document;
```

**AFTER**:
```php
// (line removed) — HasDocuments trait provides documents() via QuickerFaster\UILibrary\Models\Document
```

Also remove the import statement entirely — the line is deleted.

**Risk**: If any other code in the consuming app relied on `Employee` importing `Document` for alias resolution, it would break. However, the `documents()` MorphMany from `HasDocuments` always uses `QuickerFaster\UILibrary\Models\Document::class` (fully qualified in the trait), and no code in Employee.php references `Document` directly.

---

### Fix 4: Update `onboarding.php` to Reference Library Document Model

**Root Cause**: Additional Cause B

**File**: `app/Modules/Hr/Config/onboarding.php`

**Change** (line 55):

**BEFORE**:
```php
                'model'     => \App\Modules\Hr\Models\Document::class,
```

**AFTER**:
```php
                'model'     => \QuickerFaster\UILibrary\Models\Document::class,
```

**Risk**: The `model` key in the step config is used by the onboarding system to reference the primary model for a step. Switching to the library model aligns with the `DocumentsUploaded` condition (which queries via the Employee's `documents()` MorphMany — already using the library model).

---

### Fix 5: Update `employee.php` Data Config `documents` Relationship

**Root Cause**: Additional Cause C

**File**: `app/Modules/Hr/Data/employee.php`

**Change** (lines 396-400):

**BEFORE**:
```php
    'documents' => [
      'type' => 'hasMany',
      'model' => 'App\Modules\Hr\Models\Document',
      'foreignKey' => 'employee_id',
      'localKey' => '',
    ],
```

**AFTER**:
```php
    'documents' => [
      'type' => 'morphMany',
      'model' => 'QuickerFaster\UILibrary\Models\Document',
      'foreignKey' => '',
      'localKey' => '',
      'morphName' => 'documentable',
    ],
```

**Reason**: The Employee model's `documents()` relationship comes from `HasDocuments` trait, which is a `MorphMany` (NOT `HasMany`) using `documentable_type`/`documentable_id`. The DataTable config was incorrectly declaring it as a `hasMany` with `employee_id` FK. Changing to `morphMany` with `morphName = 'documentable'` aligns the config with the actual Eloquent relationship.

**Risk**: DataTables that use this relationship config for display/filtering may need the morph-based queries. Testing required.

---

### Fix 6: Update `DocumentsUploaded` Condition to Use Library `Document`

**Root Cause**: Cause 6 (wizard mount check)

**File**: `app/Modules/Hr/Conditions/DocumentsUploaded.php`

**Change** (lines 13-25):

**BEFORE**:
```php
class DocumentsUploaded implements OnboardingCondition
{
    public function __invoke($model): bool
    {
        $employee = \App\Modules\Hr\Models\Employee::where('user_id', $model->id)->first();

        if (! $employee) {
            return false;
        }

        return $employee->documents()->count() > 0;
    }
}
```

**AFTER**:
```php
class DocumentsUploaded implements OnboardingCondition
{
    public function __invoke($model): bool
    {
        $employee = \App\Modules\Hr\Models\Employee::withoutCompanyScope()
            ->where('user_id', $model->id)
            ->first();

        if (! $employee) {
            return false;
        }

        // Uses the HasDocuments trait's MorphMany (library Document),
        // not the legacy HR Document model's hasMany.
        return $employee->documents()->count() > 0;
    }
}
```

**Risk**: Minimal — the logic is identical but now uses `withoutCompanyScope()` for consistency with the rest of the onboarding flow.

---

### Fix 7: Add Error Handling in EmployeeOnboardingWizard `mount()`

**Root Cause**: Cause 6 (robustness)

**File**: `app/Modules/Hr/Https/Livewire/Onboarding/EmployeeOnboardingWizard.php`

**Change** (lines 80-84):

**BEFORE**:
```php
        // Step 4: Check if any documents exist
        if ($this->employee && method_exists($this->employee, 'documents') {
            if ($this->employee->documents()->count() > 0) {
                $this->completedSteps['documents'] = true;
            }
        }
```

**AFTER**:
```php
        // Step4: Check if any documents exist via the polymorphic
        // relationship provided by HasDocuments trait.
        if ($this->employee && $this->employee->relationLoaded('documents')) {
            // Already loaded — use the collection
            if ($this->employee->documents->count() > 0) {
                $this->completedSteps['documents'] = true;
            }
        } elseif ($this->employee) {
            try {
                if ($this->employee->documents()->count() > 0) {
                    $this->completedSteps['documents'] = true;
                }
            } catch (\Exception $e) {
                // Relationship may not be queryable yet (e.g., during
                // initial mount before database is fully migrated).
                // Leave the step as incomplete.
            }
        }
```

**Risk**: Low. The `try/catch` prevents a crash if the `documents` table or relationship is in a bad state. The `relationLoaded` check avoids a redundant query if the relationship was eager-loaded.

---

### Fix 8: Document the HR `Document` Model Status

**Root Cause**: Cause 2 (architectural cleanup)

**File**: `app/Modules/Hr/Models/Document.php`

**Change**: Add a docblock at the top of the class explaining its status:

**BEFORE** (lines 1-16):
```php
<?php

namespace App\Modules\Hr\Models;

use QuickerFaster\UILibrary\Traits\HasCompanyScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Modules\Hr\Models\Employee;

use Illuminate\Database\Eloquent\Model;


class Document extends Model
{
    use HasCompanyScope;
    use HasFactory;
    use SoftDeletes;
```

**AFTER**:
```php
<?php

namespace App\Modules\Hr\Models;

use QuickerFaster\UILibrary\Traits\HasCompanyScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Modules\Hr\Models\Employee;

use Illuminate\Database\Eloquent\Model;

/**
 * HR-specific Document model for the DataTable-based admin document
 * management UI (Data/document.php).
 *
 * IMPORTANT: For onboarding document uploads, the library's
 * DocumentEngine uses QuickerFaster\UILibrary\Models\Document, NOT
 * this model. This model coexists on the same 'documents' table
 * only to serve the HR DataTable CRUD interface. The Employee
 * model's documents() MorphMany relationship (via HasDocuments trait)
 * always resolves to the library Document.
 *
 * The 'creating' boot hook bridges legacy form submissions where
 * the 'document' field held the file path and employee_id was the
 * primary linking column, mapping them to the polymorphic
 * documentable_type/documentable_id columns that the library expects.
 */
class Document extends Model
{
    use HasCompanyScope;
    use HasFactory;
    use SoftDeletes;
```

**Risk**: None — documentation only.

---

## 3. Architecture Compliance Checklist

| Rule | Status | Evidence |
|------|--------|----------|
| Library must NOT reference `App\Modules\*` | ✅ Pass | All changes are in the consuming app. No library files modified. |
| One owner per `documents` table | ✅ Pass | Library owns the table. HR module ALTERs with domain columns. Fix 1 makes `employee_id` nullable so the library can create rows. Fix 2 uses `forceFill` for HR-specific column. |
| Consuming app modules self-contained | ✅ Pass | All changes are within `app/Modules/Hr/` |
| Contract pattern followed | ✅ Pass | `DocumentEngine::upload()` accepts `Documentable` contract. `Step4Documents` resolves `Employee` (which implements `Documentable`) and passes it to the engine. |

---

## 4. Execution Order

```
Step 1: Fix 1 — Run migration to make employee_id nullable
Step 2: Fix 3 — Remove unused import from Employee.php
Step 3: Fix 2 — Update Step4Documents::upload() with forceFill + document_type + try/catch
Step 4: Fix 5 — Update employee.php Data config (morphMany)
Step 5: Fix 4 — Update onboarding.php config
Step 6: Fix 6 — Update DocumentsUploaded condition
Step 7: Fix 7 — Add error handling to EmployeeOnboardingWizard::mount()
Step 8: Fix 8 — Add docblock to HR Document model
```

**Minimum viable set** (what must be done for upload to work): Fixes 1 + 2 + 3

---

## 5. Why the Button "Grays Out"

The upload silently fails because:

1. User clicks "Upload Document"
2. `wire:submit.prevent="upload"` fires
3. `$this->validate()` passes
4. `$this->engine->upload(...)` calls `Document::create([...])` 
5. MySQL rejects the INSERT — `employee_id` has no default and is NOT NULL
6. A `QueryException` is thrown but **never caught** — Livewire swallows it silently
7. The code never reaches `$this->dispatch('notify', ...)` nor `$this->reset([...])`
8. The UI appears to "gray out" — button is in `wire:loading` state but no response comes back

Fixes 1 and 2 together resolve this: Fix 1 removes the constraint violation, Fix 2 adds error handling so any future failures produce a visible error notification.

---

## 6. Files Not Modified

The following files were examined and do **not** require changes:

| File | Reason |
|------|--------|
| `src/Contracts/Documents/Documentable.php` | Contract is correct — `getDocumentType()` returns the entity-level category, which is separate from the per-document user-selected type |
| `src/Traits/Documents/HasDocuments.php` | Correctly uses library `Document::class` in MorphMany |
| `src/Services/Documents/DocumentEngine.php` | Correct — uploads polymorphically. HR domain columns are the consuming app's responsibility |
| `src/Models/Document.php` | Correct — `document_type` is in `$fillable`; `metadata` is cast to `array` |
| `app/Modules/Hr/Resources/views/onboarding/steps/step4-documents.blade.php` | Correct — collects `document_type` from user and binds it to `$document_type` |
| `app/Modules/Hr/Database/Migrations/2026_08_18_000002_add_hr_columns_to_documents_table.php` | **Do not modify** — past migration. Fix 1 creates a NEW migration instead |
| `app/Modules/Hr/Data/document.php` | Correct — serves the admin DataTable CRUD via HR Document model. Does not need to change unless the HR Document model is removed entirely |