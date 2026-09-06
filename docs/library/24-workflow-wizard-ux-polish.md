# QuickerFaster UI Library — Workflow Definition Wizard UX Polish (Summary Step)

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Status**: Implemented (2026-08-16) — Summary redesign, notification toggles, step tracker polish, and cache-busting applied
> **Scope**: UX polish for the Summary step and the overall wizard chrome of the Workflow Definition Wizard

**Related files**:
- [`workflow-definition-wizard.blade.php`](../../src/Resources/views/livewire/workflows/workflow-definition-wizard.blade.php) — the full wizard view (Summary at lines ~174–308)
- [`WorkflowDefinitionWizard.php`](../../src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) — component state and `render()`
- [`quicker-faster.css`](../../public/assets/css/quicker-faster.css) — wizard step tracker, pipeline, review-step CSS (lines ~357–495)
- [`page-header.blade.php`](../../src/Resources/views/components/layouts/partials/page-header.blade.php) — library page-header pattern (for consistency reference)
- [`22-workflow-definition-wizard-ux.md`](./22-workflow-definition-wizard-ux.md) — the prior wizard UX architecture (this doc is a focused follow-up)

> **Naming note**: This file uses the `15-` prefix at the requester's direction. It is a companion to the existing [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md); the [`00-index.md`](../README.md) file map should be updated to list it under a distinct slot (e.g., "15b" or a new descriptive entry).

> **Implementation status**: The recommendations in this document were applied on 2026-08-16. The Summary step now uses the merged two-column "Approval Flow" layout (§2), the step tracker polish (§3.1) shipped, and the stylesheet link gained a `?v=1.0.4` cache-busting query string. The notification section changed from a master "Enable workflow notifications" toggle + four free-text type inputs to **four per-event toggle switches** (`$notifyOnSubmitted`, `$notifyOnApproved`, `$notifyOnRejected`, `$notifyOnRecalled`), with the `enabled` flag derived from the individual toggles.

---

## 1. Summary Redundancy Analysis

### 1.1 What is currently rendered

The Summary step (step index `4`) renders four vertically stacked blocks inside the single outer card:

| # | Block | Blade lines | Purpose |
|---|-------|-------------|---------|
| 1 | Workflow Details card | 178–196 | Name, Key, Entity, Status badge |
| 2 | Pipeline visualization (`.qf-pipeline`) | 199–233 | Numbered horizontal nodes: `Initiator` → review steps → `Authorizer`, each showing resolution note + count |
| 3 | Detailed assignee card (`.alert alert-light`) | 235–273 | Repeats the same tiers with **actual assignee names** as badges |
| 4 | Notifications toggle card | 275–307 | Four per-event toggle switches (`$notifyOnSubmitted`, `$notifyOnApproved`, `$notifyOnRejected`, `$notifyOnRecalled`) |

### 1.2 The redundancy

Blocks 2 and 3 describe **the same underlying entities** — the initiator tier, each review step, and the authorizer tier — but at two different granularities:

| Attribute | Pipeline (block 2) | Assignee card (block 3) |
|-----------|--------------------|--------------------------|
| Tier label | `Initiator`, review name, `Authorizer` | `Initiators`, review name, `Authorizers` |
| Resolution note | "Any one can submit / review / approve" | "(any one can submit)", "(any one / all must review)", "(any one can approve)" |
| Who | Count only, e.g. `(1)` | Actual labels, e.g. `SUPER ADMIN` |
| Order | Implied by left→right arrows | Review chain explicitly listed top→bottom |

There are already subtle inconsistencies between the two (the pipeline says "Any one can review" while the card says "any one"; pluralization differs: "Initiator" vs "Initiators"). This confirms the two blocks are being maintained as separate mental models that can drift.

### 1.3 Verdict: merge into a single richer visualization

**Recommendation: merge the pipeline and the assignee card into one vertical "Approval Flow" timeline.**

The redundancy is **mildly harmful**, not catastrophic, but it costs more than it adds:

- **Two competing mental models** for the same data — a horizontal flow strip and a vertical list — force the reader to reconcile them.
- **Duplicated labels and resolution semantics** create drift risk (already observable today).
- **Wasted vertical space** on a screen whose entire job is confirmation ("did I configure this correctly?"). The user must scroll past an overview that adds nothing over the detail list.
- The horizontal `.qf-pipeline` cannot comfortably carry variable-length assignee names, which is why the names were split into a separate card in the first place.

A **single vertical timeline** resolves all of this:

- Each tier becomes one timeline node: numbered marker → tier label + resolution badge → inline assignee badges.
- Order is shown once (top-to-bottom), which is also the correct semantic for a "must approve in order" review chain.
- Names are shown inline, so there is no second "authoritative" block.
- It is naturally responsive (horizontal arrow strips wrap awkwardly on narrow screens; a vertical timeline does not).

If a compact "at-a-glance" strip is ever wanted elsewhere (e.g., a list page), the existing `.qf-pipeline` CSS can be retained as a read-only overview component — but it should **not** appear on the Summary step alongside the detail timeline.

---

## 2. Recommended Summary Layout

### 2.1 Structure

Replace the current loose vertical stacking with a **two-column layout on `lg+`**, collapsing to a single logical column on mobile:

| Column | Width (`lg+`) | Contents |
|--------|---------------|----------|
| Primary — Approval Flow | `col-lg-7` | Merged vertical timeline (initiators → review chain → authorizers, with names) |
| Secondary — Details + Notifications | `col-lg-5` | `Workflow Details` card stacked above `Notifications` card |

**Why flow-on-left / details-on-right:**
- The flow is the highest-value confirmation content on this screen; it gets the most width and the leftmost (primary) reading position.
- Details (name/key/entity/status) and notifications are lower-priority reference/settings content and stack compactly on the right.
- On mobile, the flow appears first (most important), followed by details, then notifications — matching confirmation-first reading order.

### 2.2 Card semantics

Use proper Bootstrap `card` + `card-header` for each section instead of the current `bg-light` + `h6.text-uppercase` pattern. This gives each group a clear, consistent header with an icon:

- **Approval Flow** — `card` with `card-header` ("Approval Flow" + a `fa-route`/`fa-diagram-project` icon).
- **Workflow Details** — `card` with `card-header` ("Workflow Details").
- **Notifications** — `card` with `card-header` ("Notifications"), with the four toggle switches kept inside a `card-body`.

Use `shadow-sm` consistently, matching the outer wizard card and the library's existing card styling.

### 2.3 Heading

The generic `<h4>Summary</h4>` (line 176) duplicates the step tracker's "Summary" label (line 116). Replace the in-body heading with a **confirmation context line** that shows the workflow name, e.g.:

```
Review & save "Purchase Order Approval"
```

This gives the user a concrete confirmation anchor rather than a redundant label. The tracker continues to communicate "you are on Summary".

---

## 3. Overall Wizard Polish

### 3.1 Step tracker (`.qf-wizard-steps`)

**Current state** — [`quicker-faster.css`](../../public/assets/css/quicker-faster.css:360):
- Numbered circles (`.qf-wizard-step-dot`), checkmarks for completed steps, horizontal connectors.
- **Issue**: the connector into the **active** step is colored green (`.qf-wizard-step.active .qf-wizard-step-connector`, lines 411–414). Visually this claims the segment leading *into* the active step is already "done", which is premature. Only **completed** steps should green their outgoing connector.

**Recommendations:**
- Remove `.active` from the green-connector rule so only `.completed` colors the connector. The active step's dot stays blue, its label stays blue, and the connector feeding it stays neutral grey until the step completes.
- Add `aria-current="step"` on the active `.qf-wizard-step` (accessibility).
- Add a subtle scale/ring on the active dot and a `transition` on dot background (small, consistent with the existing `.collapse-chevron` transition pattern).
- Keep labels uppercase/letter-spaced, but allow wrapping: add `line-height: 1.2` and `max-width` guard so the 5-step row does not clip on narrow screens (currently `flex: 1` on each step).

### 3.2 Step transition UX (navigation)

**Current state** — lines 312–335: Back is a text link, Cancel is a text link, Continue/Save is a large primary/success button.

**Recommendations:**
- Add an explicit **"Step X of 5"** progress label under the header (e.g., small muted text next to the step tracker or under the title). The tracker shows position visually, but a textual count is a fast, screen-reader-friendly confirmation.
- Keep the Back/Cancel text-links + large primary Continue pattern (it is sound), but ensure Back has a visible hover state (`text-decoration: underline`) and is not `disabled` when on step 1 (currently it is hidden via `opacity:0`, which is acceptable but should also have `aria-hidden`/`pointer-events: none`).
- Consider a subtle **unsaved-changes hint** on Cancel is out of scope, but the Cancel button already redirects via `wire:click="cancel"` which clears the session — leave as-is.

### 3.3 Bootstrap 5 styling opportunities

- **Card headers**: as in §2.2, use `card-header` with icons for section titles rather than `bg-light` + uppercase `h6`.
- **Badges for assignees**: standardize — `bg-primary` for users, `bg-info` for roles (already used); add a small `text-capitalize` and consistent `me-1` spacing. Introduce a `.qf-resolution-badge` for the resolution mode ("Any one can submit", "All must review", etc.) so resolution semantics read as a secondary badge rather than a parenthetical string.
- **Status badge**: already uses `bg-success`/`bg-secondary` — fine.
- **Notifications**: the four per-event notification toggle switches are bound via `wire:model` (they are the state source). The master "Enable workflow notifications" toggle is gone; `enabled` is derived from the individual toggles.

### 3.4 Completion card

**Current state** — lines 2–14: green `fa-check-circle` + title + message + "Back to Workflows".

**Recommendations (minor):**
- Include the saved workflow **name/key** in the completion message (the data is available via `$workflowName`/`$workflowKey` at completion time) so the user has immediate confirmation of *what* was saved.
- Optionally add a secondary "Create Another Workflow" action (`wire:click="reset"` or a link to the wizard route) next to "Back to Workflows". Keep it optional to avoid scope creep.

---

## 4. Implementation Checklist

### 4.1 Blade — [`workflow-definition-wizard.blade.php`](../../src/Resources/views/livewire/workflows/workflow-definition-wizard.blade.php)

1. **Replace the Summary body (lines 174–308)** with the two-column layout:
   - Remove the standalone `<h4 class="fw-bold mb-4">Summary</h4>` and replace with a confirmation heading showing the workflow name (e.g., `Review & save "{{ $workflowName ?: 'Untitled' }}"`).
   - Wrap content in `<div class="row g-4">`.
   - Left `col-lg-7`: the merged **Approval Flow** timeline (see §4.2).
   - Right `col-lg-5`: **Workflow Details** card (keep the existing `dl.row` content, move into a `card`/`card-header`) stacked above the **Notifications** card (move into `card`/`card-header`, keep the four per-event toggle switches).
2. **Delete the redundant blocks**:
   - The `.qf-pipeline` block (lines 199–233).
   - The `.alert.alert-light` assignee card (lines 235–273).
3. **Build the merged timeline** (replacing the two deleted blocks) as a `card` with `card-header` "Approval Flow" and a `card-body` containing the vertical timeline. Each node:
   - Numbered marker (reuse a `.qf-summary-timeline-marker`).
   - Tier label (`Initiator`, review `name`, `Authorizer`).
   - Resolution badge (`Any one can submit`, `Any one can review` / `All must review`, `Any one can approve`).
   - Inline assignee badges (`bg-primary` user / `bg-info` role) with the "None" empty state where applicable.
   - Vertical connector line between markers.
4. **Navigation (lines 312–335)** — optionally add a "Step X of 5" label; keep Back/Cancel/Continue logic unchanged.
5. **Step tracker (lines 22–42)** — add `aria-current="step"` to the active `.qf-wizard-step`.
6. **Completion card (lines 2–14)** — optionally inject the workflow name/key into the message.

> No PHP logic changes are strictly required for the Summary merge — the `$initiators`, `$reviewSteps`, and `$authorizers` arrays already carry labels and resolution modes. The pipeline is currently derived **inline in Blade** (lines 200–219); the merged timeline can read the same arrays directly. Optionally, a computed `getPipeline()` helper on [`WorkflowDefinitionWizard.php`](../../src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) could centralize tier ordering, but it is not required for the polish.

### 4.2 CSS — [`quicker-faster.css`](../../public/assets/css/quicker-faster.css)

1. **Adjust step tracker** (lines 360–431):
   - Change `.qf-wizard-step.active .qf-wizard-step-connector` rule so the connector is **not** green when merely active (green only for `.completed`).
   - Add `transition` on `.qf-wizard-step-dot` background/border.
   - Add a subtle active-dot scale/ring.
2. **Add new vertical timeline classes** (new block near the Summary Pipeline section, ~line 449):
   - `.qf-summary-timeline` — container, `list-style:none`, `position:relative`, zero margins.
   - `.qf-summary-timeline-item` — `position:relative; padding-left: 2.5rem; padding-bottom: 1.5rem;` with `:last-child { padding-bottom: 0 }`.
   - `.qf-summary-timeline-marker` — 28px circle, `bg-primary`, white number, centered, absolute at `left:0`.
   - `.qf-summary-timeline-connector` — 2px vertical line from the marker down to the next node (`position:absolute; left:13px; top:28px; bottom:0; background:#dee2e6`), hidden on `:last-child`.
   - `.qf-summary-timeline-content` — `font-size:0.875rem`, holds label + badges.
   - `.qf-resolution-badge` — small muted/secondary badge for resolution mode.
3. **Retain `.qf-pipeline`** (lines 449–492) as-is for potential reuse as a compact overview strip elsewhere; it is simply no longer used on the Summary step.

### 4.3 Optional component — [`WorkflowDefinitionWizard.php`](../../src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php)

- (Optional) Add a `pipelineNodes()` computed method returning a normalized `[label, resolution, assignees]` array so the Blade timeline is a clean loop and tier ordering lives in one place. Not required for the visual polish.

### 4.4 Docs — [`00-index.md`](../README.md)

- Add this file to the architecture index file map (note the `15-` prefix collision with [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md)).

---

## 5. ASCII Wireframe — Recommended Summary

### 5.1 Desktop (`lg+`) — two columns

```
+------------------------------------------------------------------------------------------+
|  STEP TRACKER  (1) Done  (2) Done  (3) Done  (4) Done  (5) Summary [active]              |
+------------------------------------------------------------------------------------------+
|  Review & save "Purchase Order Approval"                                                  |
+------------------------------------------------------------------------------------------+
|  +----------------------------------------------------+  +---------------------------+   |
|  |  APPROVAL FLOW                                     |  |  WORKFLOW DETAILS         |   |
|  |  +----------------------------------------------+  |  |  Name: Purchase Order ... |   |
|  |  | (1) Initiator      [ Any one can submit ]    |  |  |  Key:  purchase_order      |   |
|  |  |     [SUPER ADMIN]                            |  |  |  Entity: Purchase Order   |   |
|  |  +----------------------------------------------+  |  |  Status: [ Active ]       |   |
|  |         |  (connector)                            |  +---------------------------+   |
|  |  +----------------------------------------------+  |  +---------------------------+   |
|  |  | (2) Admin review  [ Any one can review ]     |  |  |  NOTIFICATIONS            |   |
|  |  |     [SUPER ADMIN]                            |  |  |  [x] Notify on submitted |   |
|  |  +----------------------------------------------+  |  |  [x] Notify on approved  |   |
|  |         |  (connector)                            |  |  [x] rejected + [x] recalled |   |
|  |  +----------------------------------------------+  |  +---------------------------+   |
|  |  | (3) Authorizer    [ Any one can approve ]    |  |                               |   |
|  |  |     [SUPER ADMIN]                            |  |                               |   |
|  |  +----------------------------------------------+  |                               |   |
|  +----------------------------------------------------+                               |   |
+------------------------------------------------------------------------------------------+
|  < Back                    [Cancel]                [ Save Workflow ]                     |
+------------------------------------------------------------------------------------------+
```

### 5.2 Mobile (`< lg`) — single column, flow-first

```
+------------------------------------------+
| STEP TRACKER (wraps / condensed)         |
+------------------------------------------+
| Review & save "Purchase Order Approval"  |
+------------------------------------------+
| APPROVAL FLOW                            |
|  (1) Initiator  [Any one can submit]     |
|      [SUPER ADMIN]                       |
|      |                                   |
|  (2) Admin review [Any one can review]   |
|      [SUPER ADMIN]                       |
|      |                                   |
|  (3) Authorizer [Any one can approve]    |
|      [SUPER ADMIN]                       |
+------------------------------------------+
| WORKFLOW DETAILS                         |
|  Name / Key / Entity / Status            |
+------------------------------------------+
| NOTIFICATIONS                            |
|  [x] Notify on submitted                 |
|  [x] Notify on approved                  |
|  [x] Notify on rejected                  |
|  [x] Notify on recalled                  |
+------------------------------------------+
| < Back            [Cancel]  [Save]       |
+------------------------------------------+
```

### 5.3 Single timeline node detail

```
(2)  Admin review        [ Any one can review ]     <- marker + label + resolution badge
     SUPER ADMIN  (bg-primary badge)                <- assignee badges inline
```
