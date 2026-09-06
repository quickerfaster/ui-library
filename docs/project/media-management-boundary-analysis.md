# Media & Document Management — Boundary Analysis

> **Status**: Recommendation (analysis only — no code changed)
> **Date**: 2026-08-18
> **Package**: `quicker-faster/ui-library`
> **Related analysis**: [`library-vs-module-boundary-analysis.md`](library-vs-module-boundary-analysis.md) (the Organization decision)
> **Questions**:
> 1. Should media/document management stay in the library, or become a module?
> 2. Concrete enterprise/freelance scenarios where Organization in the library is inappropriate.

---

# Question 1 — Should media/document management be in the library?

## 1.1 Verdict (TL;DR)

**Media/document management should STAY in the library** — but only as *generic file-attachment infrastructure* (the `Documentable` contract + `DocumentEngine` + polymorphic `Document` + preview UI). It is a **capability/mechanism**, not a business noun, and it passes the two-domain test.

The `documents` table collision with the HR app is **not** evidence that every module should own its own documents. It is evidence of two separate things:

1. **The two "documents" are conceptually different things.** The library's `documents` table is a *polymorphic file-attachment store* (`documentable_id`/`documentable_type` morphs). The HR app's `documents` table is a *business-domain entity* (`employee_id`, `company_id`, `type` = Resume/Contract/Offer Letter, `expiry_date`). They only share a name.
2. **Naming and internal consistency need cleanup.** The library itself has two divergent document paths (see §1.4), and the HR module rolls its own file storage instead of consuming the library's `Documentable` primitive.

The recommended resolution is: **the library owns the file mechanism (single source of truth for upload/store/download/preview); each module owns its business metadata** — either via a `documentable` morph to the library's `Document`, or via a properly-named domain table (e.g. `employee_documents`).

---

## 1.2 What the code actually shows

| Piece | Role | Domain-coupled? |
|-------|------|-----------------|
| [`Documentable`](../../src/Contracts/Documents/Documentable.php:5) | Contract: `getDocumentableId()` / `getDocumentType()` / `getDocumentStoragePath()` / `getDocumentTemplateData()`. | No — the entity supplies its own storage path, type key, and template data. |
| [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php:13) | Generic `upload()`, `generatePdf()`, `generateExcel()`, `getDocuments()`, `delete()`. | No — works against the contract only; no business noun in the API. |
| [`Document`](../../src/Models/Document.php:9) | Polymorphic attachment record (`documentable_type`/`documentable_id`, `file_path`, `mime_type`, `size`, `disk`, `metadata`). | No — pure mechanism, analogous to `Workflowable`. |
| [`DocumentController`](../../src/Http/Controllers/Documents/DocumentController.php:18) | Streams a file download through `AuthorizationService`. | No business noun — but see §1.4 consistency gaps. |
| [`DocumentPreview`](../../src/Http/Livewire/DocumentPreview.php:7) | Detects image/pdf/text/office/unsupported and renders the right partial. | No — purely about file MIME/extension. |
| [`DocumentPreviewModal`](../../src/Http/Livewire/Modals/DocumentPreviewModal.php:7) | Bootstrap modal wrapper around `DocumentPreview`. | No — purely presentational. |
| [`2026_08_08_000002_create_documents_table.php`](../../Database/Migrations/2026_08_08_000002_create_documents_table.php:11) | `documents` table with a `morphs('documentable')`. | No — polymorphic infrastructure schema. |
| Views under [`src/Resources/views/livewire/documents/`](../../src/Resources/views/livewire/documents/document-preview.blade.php:1) | Image/PDF/text/office/unsupported partials. | No — all file-type rendering, zero HR/domain text. |

Contrast with the HR consuming app's `Document` (`/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Models/Document.php`):

- Concrete columns `company_id`, `employee_id`, `name`, `type`, `document`, `uploaded_at`, `expiry_date`, `description`.
- `type` is an HR enum: Resume / Contract / Offer Letter / ID Proof / Visa / Certificate / Performance Review / Other (see `/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Data/document.php:59`).
- `belongsTo` `Employee` and `Company`, uses [`HasCompanyScope`](../../src/Traits/HasCompanyScope.php).
- Its own `documents` migration with `employee_id` and `company_id` FKs (`/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Database/Migrations/2026_06_12_142507_create_documents_table.php:11`).

These are **two different entities that happen to share the noun "document."**

---

## 1.3 Domain-agnostic test and two-domain test

**Is the library's Document system domain-agnostic?**

Yes. The entire API surface is expressed as a contract and a polymorphic record:

- Any Eloquent model can implement [`Documentable`](../../src/Contracts/Documents/Documentable.php) and answer four questions: *what's your ID, what's your document-type key, where do your files go, what data feeds your generated templates?*
- [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php) is agnostic to *what* the entity is. It stores a file, records a morph row, and (optionally) generates PDF/Excel output. There is no `Employee`, `Invoice`, or `Payroll` anywhere in it.
- The preview stack only reasons about MIME type and file extension.

**Does it pass the two-domain test?** Yes — convincingly:

| Domain | How it would use the library's Document system |
|--------|------------------------------------------------|
| **Inventory** | Attach product photos, spec sheets, supplier certificates to `Product` / `Supplier` via `documentable`. |
| **Accounting** | Attach invoices, receipts, and statements to `Transaction` / `Vendor`; `generatePdf()` for invoice output. |
| **HR** | Attach resumes, contracts, and offer letters to `Employee`; `generatePdf()` for offer letters. |

All three use the identical mechanism for the identical reason: "attach a file (or generate one) and be able to store, list, download, and preview it." That is the textbook definition of reusable library infrastructure.

---

## 1.4 The collision is real, but it does not mean "each module owns its own documents"

The HR app creates a `documents` table (`/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Database/Migrations/2026_06_12_142507_create_documents_table.php`), and the library ships a `documents` table ([`2026_08_08_000002_create_documents_table.php`](../../Database/Migrations/2026_08_08_000002_create_documents_table.php)). Two readings of this:

**Reading A (wrong conclusion): "the library's Document is too opinionated, so modules should own documents."**

This misdiagnoses the problem. The two schemas are not competing implementations of the same thing — they are different things:

- Library: `documentable_id` + `documentable_type` morph → *"a file attached to some entity."*
- HR: `employee_id` + `company_id` + `type` + `expiry_date` → *"an employee's HR document record."*

**Reading B (correct conclusion): the library owns the mechanism; the module owns the business noun.**

The HR app's `documents` table is really an **`employee_documents`** table that was named too generically. Its domain-specific columns (`employee_id`, `type` enum, `expiry_date`) can never belong in a domain-agnostic library — but the *file plumbing* (store the blob, record its path/MIME/size, stream the download, render the preview) absolutely can.

So the collision is a symptom of:

1. **Overloaded naming** — "document" is used both for a polymorphic attachment and for a domain record. The library should consider a more precise name for its primitive (e.g. `attachments`/`media`), and modules should prefix domain tables (`employee_documents`).
2. **The HR module re-implementing file storage** instead of consuming [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php). Its `document` column stores a file path, duplicating what the library already does better.
3. **A latent internal inconsistency in the library** that mirrors the external collision (below).

### Internal consistency gaps to fix (stay-in-library, but clean up)

These do not change the verdict, but they must be resolved so the "single source of truth" claim actually holds:

| Issue | Evidence | Fix |
|-------|----------|-----|
| `DocumentController` expects `$document->document`, but the library's own `Document` model uses `file_path` | [`DocumentController.php:26`](../../src/Http/Controllers/Documents/DocumentController.php:26) vs [`Document.php:15`](../../src/Models/Document.php:15) | Make the controller/`Document` field names consistent (`file_path`), or accept a normalized accessor. Today `Document::getDownloadUrl()` → `documents.download` route would 404/error because `document` is not set. |
| `DocumentController` hardcodes `disk('documents')` and a `documents` disk, while the engine defaults to `config('ui-library.documents.disk')` = `public` | [`DocumentController.php:26`](../../src/Http/Controllers/Documents/DocumentController.php:26), [`DocumentEngine.php:19`](../../src/Services/Documents/DocumentEngine.php:19), [`ui-library.php:470`](../../src/Config/ui-library.php:470) | Resolve the disk from config consistently; stop hardcoding two disk names. |
| `DataTableForm` hardcodes `QuickerFaster\UILibrary\Models\Document::class` and the `document`/`documents` field+disk names | [`DataTableForm.php:1250`](../../src/Http/Livewire/DataTables/DataTableForm.php:1250) | This couples the generic form engine to the concrete `Document` model — the same class of leak flagged for `Company` in the prior analysis. Decouple via config/contract. |
| `FileField` guesses "is a document" by `subject`/`owner`/`user` relationships and hardcodes the `documents.download` route | [`FileField.php:45`](../../src/Components/FieldTypes/FileField.php:45) | Make the download-route/detection explicit via field definition instead of name-heuristic. |

The common thread: the library's Document *concept* is correctly generic, but its *implementation* has a few hardcoded names and a field-name mismatch. That is a refactoring task, not a boundary change.

---

## 1.5 Applying the decision rule from the prior analysis

Using the same table from [`library-vs-module-boundary-analysis.md`](library-vs-module-boundary-analysis.md:244):

| If the feature… | Then it goes… | Applied to Document/media |
|-----------------|---------------|---------------------------|
| Is a **contract, engine, scope, middleware, trait, or config seam** with no business noun | **Library** | ✅ `Documentable`, `DocumentEngine`, `Document` polymorphic model, preview UI — all mechanism. |
| Passes the **two-domain test** and ships a **null/no-op default** | **Library** | ✅ Inventory + Accounting + HR all attach files identically. |
| Names a **business entity** or owns **domain-specific tables/views/routes** | **Module** | ⚠️ HR's `employee_id` + `type` enum + `expiry_date` — this is the *module's* part. |
| Only makes sense for **one domain** | **Module** | ✅ HR's "Visa / Offer Letter / Resume" type enum — module-owned. |
| Is a **shared structural concept that many modules import** | **Foundational module** | N/A — file attachment is mechanism, not a business noun. |

**Result: the mechanism stays in the library; the business record stays in the module.**

Concretely:

- **Library keeps** (as generic file infrastructure):
  - [`Documentable`](../../src/Contracts/Documents/Documentable.php) contract.
  - [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php) (upload / generatePdf / generateExcel / get / delete).
  - The polymorphic [`Document`](../../src/Models/Document.php) model + its `documents` (or renamed `attachments`) migration.
  - [`DocumentController`](../../src/Http/Controllers/Documents/DocumentController.php), [`DocumentPreview`](../../src/Http/Livewire/DocumentPreview.php), [`DocumentPreviewModal`](../../src/Http/Livewire/Modals/DocumentPreviewModal.php), and the preview views.
  - A `HasDocuments`/`Documentable` default trait that modules can drop onto their models (so the four contract methods get sensible defaults).
- **Each module owns** its domain record. HR has two valid choices:
  1. **Use the library primitive + a thin domain table:** a polymorphic `documentable` on `Employee` for the actual files, plus a separate `employee_documents` metadata row (`type`, `expiry_date`, `employee_id`, `company_id`) if HR-specific fields are needed. This is the cleanest.
  2. **Keep a standalone domain table**, but rename it `employee_documents` (or `hr_documents`) so it never collides with the library's polymorphic table, and stop duplicating file-storage logic — call `DocumentEngine` for the blob mechanics.

---

# Question 2 — Enterprise/freelance scenarios where Organization in the library is inappropriate

## 2.1 Verdict (TL;DR)

Moving Organization out of the library is correct because Organization is a **business noun with an opinionated hierarchy** (Company → Branch/Division/Department/BusinessUnit → Team → Location). The library's tenancy *mechanism* (`company_id` scope + `CompanyProvider`) is universal, but the full org-chart *entity graph* is not.

The table below makes the failure concrete: in every scenario, the app needs a *different* org model, and the library's baked-in hierarchy is either dead weight or forces awkward workarounds.

## 2.2 Scenario table

| Scenario | What org concepts they ACTUALLY need | Why the library's Organization domain is dead weight / forces workarounds | How "Organization as a copyable module" solves it |
|----------|--------------------------------------|---------------------------------------------------------------------------|--------------------------------------------------|
| **Freelance invoicing app** (sole proprietor) | A single `Company` (their own business) and a list of `Client` records. No departments, teams, branches, or locations. | The library ships 7 tables + 3 dashboards + an org-chart CRUD UI the user never opens. Worse, the tenant term `company_id` is conflated with the org entity, so a solo owner must provision a "Company" just to satisfy the tenancy layer. | The freelancer simply doesn't copy the Organization module. The library's `CompanyScope`/`CompanyProvider` still give them single-tenant `company_id` scoping, and their own `Client` model handles customers. Zero dead schema, zero unused menus. |
| **Inventory / warehouse management** | `Warehouse`, `StorageLocation` (aisle/rack/bin), `StockItem`, `Bin`. Tenancy is by warehouse/facility, not by company hierarchy. | `Branch`, `Division`, `Department`, `BusinessUnit`, and `Team` are meaningless for a warehouse. The team would have to map "Department" → "warehouse zone" or "Team" → "picking crew" — a forced, leaky abstraction that fights the UI labels and reports. | The team copies (or writes) an `Inventory` module whose own `Warehouse`/`Location` tables model the real hierarchy. `CompanyScope` still gives tenant isolation, but no org-chart nouns pollute the schema. |
| **SaaS multi-tenant CRM** | `Account` (the tenant), `Contact`, `Territory`, `SalesPerson`, `Lead`. Organization is *accounts and sales territories*, not departments and divisions. | The library's `Department`/`Team` hierarchy is an HR/org-chart model that has no place in CRM. Forcing "Account → Department → Team" mislabels the domain; `Territory` (a sales geography) has no natural home in the shipped graph. | A `CRM` module owns `Account`, `Contact`, `Territory`, etc. The library supplies tenancy (`company_id`) and shared UI (DataTable/form/report). No org-chart schema is imposed. |
| **Project management tool** | `Workspace`, `Project`, `Epic`/`Task`, `Assignee`, `Team` (a *project* team, not an org-chart team). | The library's org `Team` means "departmental reporting unit," not "cross-functional project squad." `Division`/`BusinessUnit` are irrelevant. The tool would fight the library's org tables to express "this team spans three departments." | A `Projects` module defines its own `Workspace`/`Project`/`Team` entities. `CompanyProvider` provides workspace/tenant switching; the project hierarchy is the module's own schema. |
| **School management system** | `Campus`, `Faculty`, `Department` (academic), `Programme`, `Class`, `Section`, `Student`. Hierarchies differ (academic vs administrative) and include calendar/session concepts. | The library's `Company → Division → BusinessUnit → Department → Team` is a corporate model; a school needs `Campus → Faculty → Programme → Class`, plus *academic sessions*, which don't exist in the library at all. Reusing corporate `Team` for a "Class" is a semantic mismatch. | A `School` module (or a `Campus` foundational module) owns its own hierarchy. The library still contributes auth, tenancy, DataTable CRUD, and reporting — none of which are school-specific. |

## 2.3 The pattern across all scenarios

Every scenario needs the same **two things**, but the library only correctly provides one of them:

1. **The tenancy/mechanism layer (universal)** — "scope records by a tenant id," "resolve the current tenant," "switch tenants." This is the library's job and stays.
2. **The org-hierarchy layer (domain-specific)** — "what are the organizational units and how do they nest." This varies per app and must not be frozen in the library.

Baking #2 into the library forces every app to inherit a corporate org chart it may not want. Making #2 a **copyable foundational module** keeps the reusable mechanism in the library while letting each app pick (or omit) the hierarchy that fits its domain. That is exactly the same reasoning that justified the Organization decision, and it is why the library must keep the *generic tenancy contract* while the *concrete Organization entity graph* moves out.

---

## Appendix — Evidence inventory (Question 1)

| Claim | Evidence |
|-------|----------|
| Library Document is a polymorphic attachment store | [`2026_08_08_000002_create_documents_table.php:13`](../../Database/Migrations/2026_08_08_000002_create_documents_table.php:13) (`morphs('documentable')`), [`Document.php:31`](../../src/Models/Document.php:31) (`morphTo()`) |
| Engine is contract-driven, no business noun | [`DocumentEngine.php:25`](../../src/Services/Documents/DocumentEngine.php:25) (`upload(Documentable $entity, ...)`), [`Documentable.php:5`](../../src/Contracts/Documents/Documentable.php:5) |
| Preview UI reasons only about file type | [`DocumentPreview.php:19`](../../src/Http/Livewire/DocumentPreview.php:19) (MIME/extension detection) |
| HR Document is a domain entity, not a file primitive | HR `Document.php` (columns `employee_id`, `type`, `expiry_date`), HR `document.php` (`type` enum Resume/Contract/Offer Letter/…) |
| The collision: two different `documents` tables | Library [`2026_08_08_000002_create_documents_table.php`](../../Database/Migrations/2026_08_08_000002_create_documents_table.php:11) vs HR `2026_06_12_142507_create_documents_table.php` |
| Internal field-name mismatch | [`DocumentController.php:26`](../../src/Http/Controllers/Documents/DocumentController.php:26) (`$document->document`) vs [`Document.php:15`](../../src/Models/Document.php:15) (`file_path`) |
| Form engine hardcodes the concrete Document model | [`DataTableForm.php:1250`](../../src/Http/Livewire/DataTables/DataTableForm.php:1250) |
