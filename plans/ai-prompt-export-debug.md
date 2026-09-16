# AI Prompt: Debug DataTable Export Feature

## Context

You are Zoo, an expert software debugger working on the **QuickerFaster UI Library** — a domain-agnostic, decoupled Laravel + Livewire 3 foundation. The consuming app is an HR system at `/Users/mac/Projects/LaravelProjects/hr-consuming-app`.

## The Problem

When clicking "Export" on any DataTable, the export modal shows **"Initializing export..."** and stays stuck. The export never progresses to completion.

## Library Philosophy (MUST RESPECT)

From [`docs/library/pilosophy.txt`](../../docs/library/pilosophy.txt):
1. Library must be completely decoupled from the consuming app
2. Consuming app modules must be self-contained and plug-and-play

From [`docs/library/25-library-independence-safeguards.md`](../../docs/library/25-library-independence-safeguards.md):
- No `use App\Modules\...` in `src/`
- Contracts named by capability, never by domain
- Extension point first: Contract → Default impl → Config-key binding

## Architecture — Export Flow

```
User clicks "Export" on DataTable
  │
  ▼
[`DataTable::exportAll()`](../../src/Http/Livewire/DataTables/DataTable.php:2546)
  │  Builds params (configKey, format, columns, filters)
  │  Dispatches 'openExportModal' event
  ▼
[`export-modal.blade.php`](../../src/Resources/views/livewire/modals/export-modal.blade.php:114)
  │  Listens for 'startExport' event
  │  POST to `route('export.queue')` → ExportController
  ▼
[`ExportController::queueExport()`](../../src/Http/Controllers/Exports/ExportController.php:185)
  │  Creates Export record (status: pending)
  │  Dispatches GenerateExport job
  ▼
[`GenerateExport`](../../src/Jobs/GenerateExport.php:28)
  │  Explicit: $connection = 'database', $queue = 'default'
  │  Counts rows, dispatches ExportChunk jobs for each chunk
  ▼
[`ExportChunk`](../../src/Jobs/ExportChunk.php:30)
  │  Explicit: $connection = 'database', $queue = 'default'
  │  Processes one chunk → DataTableExport → Excel::store()
  │  After each chunk: checkAndFinalizeExport()
  ▼
[`FinalizeExportZip`](../../src/Jobs/FinalizeExportZip.php:24)
  │  Explicit: $connection = 'database', $queue = 'default'
  │  Zips all partial files, updates Export status → 'completed'
  ▼
Modal JS polls `/export/status/{exportId}` every 2s
  │  On 'completed': shows download button
  │  On 'failed': shows error
```

## Key Files

| File | Role |
|---|---|
| [`src/Http/Livewire/DataTables/DataTable.php:2546`](../../src/Http/Livewire/DataTables/DataTable.php:2546) | `exportAll()` — entry point |
| [`src/Resources/views/livewire/modals/export-modal.blade.php`](../../src/Resources/views/livewire/modals/export-modal.blade.php) | Export progress modal with JS polling |
| [`src/Http/Controllers/Exports/ExportController.php`](../../src/Http/Controllers/Exports/ExportController.php) | `queueExport()`, status endpoint, download |
| [`src/Jobs/GenerateExport.php`](../../src/Jobs/GenerateExport.php) | Orchestrator — counts rows, dispatches chunks |
| [`src/Jobs/ExportChunk.php`](../../src/Jobs/ExportChunk.php) | Processes one chunk of records |
| [`src/Jobs/FinalizeExportZip.php`](../../src/Jobs/FinalizeExportZip.php) | Zips partial files into final download |
| [`src/Models/Export.php`](../../src/Models/Export.php) | Export tracking model |
| [`src/Models/ExportChunk.php`](../../src/Models/ExportChunk.php) | Per-chunk file tracking |
| [`src/Services/Exports/DataTableExport.php`](../../src/Services/Exports/DataTableExport.php) | Maatwebsite Excel export class |
| [`src/Routes/web.php`](../../src/Routes/web.php) | Export routes (queue, status, cancel, download) |
| [`.env`](../../.env) | `QUEUE_CONNECTION=sync` (local) |

## Initial Diagnosis

The three export jobs (`GenerateExport`, `ExportChunk`, `FinalizeExportZip`) all hardcode `$connection = 'database'` and `$queue = 'default'`. This **overrides** the app's `QUEUE_CONNECTION` env setting (which is `sync` locally and likely `database` on production). 

On `sync` connection, jobs run immediately in-process. But these jobs explicitly target the `database` queue driver, which requires a running queue worker (`php artisan queue:work`).

**Most likely cause**: The jobs are dispatched to the `database` queue but no worker is processing them. The Export record stays in `pending` status, and the modal never progresses past "Initializing export..."

**Alternative causes to investigate**:
1. The `route('export.queue')` POST fails silently (CSRF, auth, validation)
2. `GenerateExport` throws before dispatching chunks (permissions, model resolution)
3. The `database` queue driver requires a `jobs` table migration that may not have run
4. The `ExportController::queueExport()` return doesn't match what the modal JS expects

## Debugging Instructions

1. **Reflect on 5-7 possible sources**, distill to 1-2 most likely
2. **Add targeted logging** to validate assumptions before fixing
3. **Check the export routes** are registered in [`src/Routes/web.php`](../../src/Routes/web.php)
4. **Verify the `jobs` table exists** (required by `database` queue driver)
5. **Test the queue flow**: dispatch a `GenerateExport` job manually via tinker
6. **Check the modal JS console** for failed POST requests
7. **Consider whether the jobs should respect `QUEUE_CONNECTION`** instead of hardcoding `database`

## Remember

- The library must remain domain-agnostic — no HR-specific logic in `src/`
- All fixes should respect the contract-first design pattern
- Reference files using clickable links: `[`filename`](relative/path:line)`