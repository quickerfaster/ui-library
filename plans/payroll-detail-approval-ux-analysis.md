# PayrollRun Detail Page — Approval Actions + Timeline UX Analysis

**Date:** 2026-08-31
**Scope:** QuickerFaster UI Library — Approval Components
**Status:** Phases 1–3 Complete
**Audience:** Library maintainers and consuming-app developers

---

## Table of Contents

1. [Current State](#1-current-state)
2. [Button Overlap Analysis](#2-button-overlap-analysis)
3. [Approval Actions Layout Problem](#3-approval-actions-layout-problem)
4. [Timeline Placement Problem](#4-timeline-placement-problem)
5. [Recommended Layout](#5-recommended-layout)
6. [Library Component Improvements](#6-library-component-improvements)
7. [Implementation Roadmap](#7-implementation-roadmap)

---

## 1. Current State

### 1.1 The PayrollRun Detail Page

**File:** `app/Modules/Payroll/Resources/views/payroll-runs/show.blade.php` (consuming app)

The page embeds two library approval components, both gated by `$showApprovalUi`:

| Position | Component | Tag | Wrapper |
|----------|-----------|-----|---------|
| Top | [`ApprovalActions`](src/Http/Livewire/Approvals/ApprovalActions.php) | `qf.approval-actions` | Full-width card |
| Bottom | [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) | `qf.approval-history-timeline` | Full-width card |

Between these two cards sits the main PayrollRun content (tabs, data, business actions).

### 1.2 The Library Components (as they render)

**`actions.blade.php`** ([`src/Resources/views/livewire/approvals/actions.blade.php`](src/Resources/views/livewire/approvals/actions.blade.php)) renders:

```
┌─────────────────────────────────────────────────────────────┐
│ [✓ Approve]  [✗ Reject]  [↩ Recall]   [Status Badge]       │
│                                                             │
│ 👤 Awaiting: [Payroll Officer] [HR Manager]                 │
└─────────────────────────────────────────────────────────────┘
```

- A `d-flex flex-wrap gap-2` row with 3 `btn-sm` buttons + status badge
- An "Awaiting" line showing approver badges with avatars
- A comment modal (Bootstrap 5, no Alpine dependency)
- **No card wrapper** — the card is added by the consuming app

**`timeline.blade.php`** ([`src/Resources/views/livewire/approvals/timeline.blade.php`](src/Resources/views/livewire/approvals/timeline.blade.php)) renders:

```
┌─────────────────────────────────────────────────────────────┐
│ Workflow Timeline                                           │
│                                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 👤 Submitted                    [Pending]                │ │
│ │    John Doe · Payroll Officer    Aug 30, 2026 14:00     │ │
│ │ 💬 "Ready for review"                                   │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ 👤 Approved                     [Approved]               │ │
│ │    Jane Smith · HR Manager       Aug 30, 2026 15:30     │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

- A `list-group` of workflow actions with actor avatars, step names, status badges, timestamps, and comments
- **No card wrapper** — the card is added by the consuming app

### 1.3 The Old Backup (Pre-Decoupling)

**File:** `/Users/mac/Projects/Libraries/modules backup/Modules/Hr/Resources/views/livewire/payroll/payroll-run-detail.blade.php`

The old page had:

```
┌─────────────────────────────────────────────────────────────┐
│ [Approve] [Mark Paid] [Cancel] [Recalculate]  [Reports ▾]   │
│                                                             │
│ ═══════════ Status: Draft ═══════════════════════════════   │
│                                                             │
│ [Overview] [Payslips] [Adjustments] [Reconciliation] [Audit]│
│                                                             │
│ (tab content)                                               │
└─────────────────────────────────────────────────────────────┘
```

- **5 buttons** in a button group: Approve, Mark Paid, Cancel, Recalculate, Reports dropdown
- **Status banner** showing current status
- **5 tabs**: Overview, Payslips, Adjustments, Reconciliation, Audit

### 1.4 Existing Library Layout Primitives

The library already ships reusable layout containers that could be leveraged:

| Component | File | Purpose |
|-----------|------|---------|
| [`qf.drawer`](src/Resources/views/livewire/drawer.blade.php) | `drawer.blade.php` | Bootstrap 5 offcanvas (right-side slide-in panel). Accepts `$title`, `$component`, `$componentParams`. |
| [`qf.collapsible`](src/Resources/views/livewire/collapsible.blade.php) | `collapsible.blade.php` | Expandable content area. Accepts `$title`, `$component`, `$componentParams`, `$isOpen`. |
| [`qf.filter-panel`](src/Resources/views/livewire/filter-panel.blade.php) | `filter-panel.blade.php` | Card-based filter panel with save/load. |

---

## 2. Button Overlap Analysis

### 2.1 Mapping Old Buttons to Library Equivalents

| Old Button | Library Equivalent | Semantic Match? | Recommendation |
|------------|-------------------|-----------------|----------------|
| **Approve** | `qf.approval-actions` → Approve | ✅ **Exact match** | **Remove.** The library's Approve button delegates to `WorkflowEngine::approve()`, which validates authorization, records the action, advances steps, and dispatches events. The old inline `approve()` method directly manipulated `$this->run->status` — this is now redundant and should be fully replaced. |
| **Cancel** | `qf.approval-actions` → Reject? | ⚠️ **Semantic mismatch** | **Analyze carefully.** "Cancel" in the old system likely meant "cancel this payroll run" (a business action that terminates the run itself). "Reject" in the library means "reject this workflow step" (an approval action that sends the workflow back). These are **different concepts**: Cancel = kill the entity, Reject = reject the approval. The library's **Recall** action is closer to Cancel — it cancels the workflow and returns the entity to draft state. **Recommendation:** Map old "Cancel" to library "Recall" (for workflow cancellation). If a separate "Cancel Payroll Run" business action is needed (destroying the run entirely), keep it as a business-specific action outside the approval component. |
| **Mark Paid** | None | ❌ **No equivalent** | **Keep.** This is a business action, not an approval action. Marking a payroll run as "paid" is a post-approval financial operation. The library has no concept of payment status. This should remain as a consuming-app button, gated on workflow completion (`!$run->isUnderApproval()`). |
| **Recalculate** | None | ❌ **No equivalent** | **Keep.** Recalculating a payroll run (re-running tax computations, deductions, etc.) is a domain-specific business operation. The library has no concept of payroll calculation. This should remain as a consuming-app button, likely only available when the run is in draft or recalled state. |
| **Reports dropdown** | None | ❌ **No equivalent** | **Keep.** Generating payroll reports (payslip PDFs, tax summaries, bank files) is a business output operation. This should remain as a consuming-app dropdown. |

### 2.2 Summary of Button Disposition

```
LIBRARY HANDLES (remove old):
  ✅ Approve     → qf.approval-actions (Approve button)
  ✅ Cancel      → qf.approval-actions (Recall button — cancels workflow)

CONSUMING APP KEEPS (business actions):
  🔵 Mark Paid     — Post-approval financial action (gate on workflow completion)
  🔵 Recalculate   — Domain-specific recalculation (gate on draft/recalled state)
  🔵 Reports       — Business output generation (always available)
```

### 2.3 The "Cancel vs Reject" Distinction

This is the most important semantic distinction in the analysis:

| Concept | Library Term | What Happens |
|---------|-------------|--------------|
| Reject an approval step | **Reject** | Current step marked rejected. Workflow status → `rejected`. Entity remains but approval is denied. Submitter can recall and resubmit. |
| Cancel the workflow | **Recall** | Workflow status → `cancelled`. Entity returns to draft/pre-submission state. Only the original submitter can recall. |
| Cancel/destroy the entity | **N/A** | The payroll run itself is deleted or marked void. This is a business action, not a workflow action. |

**Recommendation:** The old "Cancel" button should be split into two concepts:
1. **Recall** (library handles) — cancels the approval workflow, returns run to draft
2. **Void/Cancel Run** (consuming app keeps) — if the business requires destroying the run entirely

---

## 3. Approval Actions Layout Problem

### 3.1 The Problem

The `qf.approval-actions` component renders 3 small `btn-sm` buttons (Approve, Reject, Recall) plus a status badge and an "Awaiting" line. When wrapped in a full-width card at the top of the page, this occupies an entire row for ~300px of content:

```
┌──────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  [✓ Approve]  [✗ Reject]  [↩ Recall]   [Pending]                        │
│  👤 Awaiting: [Payroll Officer] [HR Manager]                             │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

This wastes significant horizontal space and pushes the actual PayrollRun content further down the page. The card is visually heavy for what amounts to a small button group.

### 3.2 UX Research: How Enterprise Apps Handle Approval Actions

#### Pattern A: Page Header / Top Bar Integration

**Used by:** GitHub (PR review), GitLab (MR approval), Jira (issue transitions)

Approval actions are integrated directly into the page header or a sticky top bar, not in a separate card:

```
GitHub PR:
┌─ Pull Request #123 ──────────────────── [Edit] [👀 Watch] ──────────────┐
│ Open · John wants to merge 3 commits into main from feature/branch      │
│                                                                          │
│ ✅ Checks passed   🔴 1 review required   [Merge pull request ▾]        │
└──────────────────────────────────────────────────────────────────────────┘

GitLab MR:
┌─ Merge Request !456 ────────────────────────────────────────────────────┐
│ Feature: Add payroll approval · Draft · John Doe                         │
│                                                                          │
│ [Approve] [Approve and Comment]   Pipeline: ✅   [Mark as ready] [Close] │
└──────────────────────────────────────────────────────────────────────────┘
```

**Key characteristics:**
- Approval actions live in the **header area**, not in a separate card
- They're **right-aligned** alongside metadata (status, pipeline, author)
- The header is often **sticky** so actions remain visible while scrolling
- Buttons are **prominent** (not `btn-sm`) because they're the primary action on the page

#### Pattern B: Sticky Bottom Bar / Action Bar

**Used by:** SAP SuccessFactors, Oracle HCM (transaction pages), ServiceNow (approval records)

A fixed bar at the bottom of the viewport contains the primary actions:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ (page content scrolls above)                                             │
├──────────────────────────────────────────────────────────────────────────┤
│  [Save]  [Submit for Approval]  [Cancel]              Last saved: 14:30  │
└──────────────────────────────────────────────────────────────────────────┘
```

**Key characteristics:**
- Actions are **always visible** regardless of scroll position
- The bar is **full-width** but **thin** (48-56px height)
- Primary action is left-aligned, secondary actions follow
- Status/metadata is right-aligned
- Common in form-heavy enterprise pages where users need to scroll through data before acting

#### Pattern C: Sidebar Action Panel

**Used by:** Workday (business process transactions), some SAP Fiori apps

Approval actions live in a right-side panel:

```
┌──────────────────────────────┬──────────────────────┐
│                              │  Status: Pending      │
│  (main content area)         │                      │
│                              │  [✓ Approve]          │
│  Payroll Run Details         │  [✗ Reject]           │
│  Period: Jan 2026            │  [↩ Recall]           │
│  Employees: 245              │                      │
│  Total: $1,245,000           │  Awaiting:            │
│                              │  👤 Payroll Officer   │
│  (tabs, data tables, etc.)   │  👤 HR Manager        │
│                              │                      │
│                              │  ── Timeline ──       │
│                              │  ● Submitted          │
│                              │  ○ HR Review          │
└──────────────────────────────┴──────────────────────┘
```

**Key characteristics:**
- Actions are **always visible** in a fixed sidebar
- The sidebar can also contain the **timeline/progress**
- Main content area gets full width for data
- Common in enterprise apps with complex approval workflows
- Works well on desktop; collapses to bottom bar on mobile

#### Pattern D: Inline with Content Header

**Used by:** Confluence (page approvals), SharePoint

Approval actions appear inline with the content header, often as a banner:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ ⚠️ This payroll run is pending approval.                                 │
│                                                                          │
│ [✓ Approve]  [✗ Reject]  [↩ Recall]    Awaiting: Payroll Officer, HR Mgr│
└──────────────────────────────────────────────────────────────────────────┘
```

**Key characteristics:**
- Uses an **alert/banner** pattern (colored background, icon)
- Actions are **inline with the status message**
- Less visually heavy than a card
- Dismissible or collapsible once action is taken
- Good for informational/status communication

### 3.3 Recommendation: Hybrid Header + Banner Pattern

For the PayrollRun detail page within the Bootstrap/Soft UI Dashboard framework, the **header integration + collapsible banner** pattern is recommended:

**When workflow is pending (actions needed):**
- Show a **colored alert banner** (not a card) at the top of the content area
- Banner contains: status badge + action buttons + awaiting approvers
- Banner uses `alert-warning` (pending) or `alert-info` styling
- Banner is **dismissible** after the user has seen it (but reappears on refresh if still pending)

**When workflow is completed (no actions needed):**
- Show a compact **status badge** in the page header (e.g., next to the title)
- No banner needed — the timeline provides the audit trail

```
RECOMMENDED — Pending State:
┌──────────────────────────────────────────────────────────────────────────┐
│ Payroll Run #PR-2026-008 · January 2026          [Mark Paid] [Reports ▾] │
│ ═══════════════════════════════════════════════════════════════════════ │
│ ⚠️ Pending Approval — Step 1 of 2: Payroll Officer Review                │
│ [✓ Approve]  [✗ Reject]  [↩ Recall]    👤 Awaiting: J. Smith, A. Jones  │
├──────────────────────────────────────────────────────────────────────────┤
│ [Overview] [Payslips] [Adjustments] [Reconciliation] [Audit]             │
│ (tab content)                                                            │
└──────────────────────────────────────────────────────────────────────────┘

RECOMMENDED — Completed State:
┌──────────────────────────────────────────────────────────────────────────┐
│ Payroll Run #PR-2026-008 · January 2026  ✅ Approved  [Mark Paid] [Rep.] │
│ ═══════════════════════════════════════════════════════════════════════ │
│ [Overview] [Payslips] [Adjustments] [Reconciliation] [Audit]             │
│ (tab content)                                                            │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Timeline Placement Problem

### 4.1 The Problem

The `qf.approval-history-timeline` component renders a chronological list of workflow actions. When wrapped in a full-width card at the bottom of the page, it:

1. **Dominates the page bottom** — a full-width card for what is essentially a vertical list
2. **Forces scrolling** — users must scroll past all tab content to see the timeline
3. **Disconnects from actions** — the timeline (history) is far from the actions (present/future)
4. **Competes with tab content** — the timeline is always visible even when viewing tabs where it's irrelevant (e.g., Reports)

### 4.2 UX Research: How Applications Handle Workflow History

#### Pattern A: Dedicated "Activity" / "History" Tab

**Used by:** Jira (issue Activity), ServiceNow (Audit History), Zendesk (Events)

The timeline lives inside a tab alongside other content tabs:

```
[Overview] [Payslips] [Adjustments] [Reconciliation] [Audit] [Activity]
```

**Key characteristics:**
- Timeline is **one tab among many** — doesn't compete for space
- Users **choose** to view it; not forced
- Tab can show a **badge** with the count of new events
- Content area is shared — timeline gets full width when active
- **Pro:** Clean, familiar, no layout disruption
- **Con:** Timeline not visible alongside other content; requires tab switch

#### Pattern B: Collapsible Sidebar / Right Panel

**Used by:** Asana (task activity), Monday.com (updates panel), Notion (page history)

The timeline lives in a collapsible right sidebar:

```
┌────────────────────────────────┬──────────────────────┐
│                                │  Activity  [✕]       │
│  (main content)                │                      │
│                                │  ● Submitted         │
│  Payroll Run Details           │    John Doe          │
│  Tabs, data, etc.              │    Aug 30, 14:00     │
│                                │                      │
│                                │  ● Approved          │
│                                │    Jane Smith        │
│                                │    Aug 30, 15:30     │
│                                │                      │
│                                │  ○ Pending           │
│                                │    HR Manager        │
└────────────────────────────────┴──────────────────────┘
```

**Key characteristics:**
- Timeline is **always accessible** via a toggle button
- Main content gets **full width** when sidebar is collapsed
- Timeline is **visible alongside** content when open
- **Pro:** Best of both worlds — hidden by default, visible on demand, doesn't interrupt workflow
- **Con:** Reduces content width when open; requires toggle interaction

#### Pattern C: Accordion / Collapsible Section

**Used by:** Confluence (page history), some SAP Fiori apps

The timeline is a collapsible section within the page, often below the main content:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ (main content)                                                            │
│                                                                           │
│ ▼ Workflow History (3 events)                          [Expand/Collapse]  │
│ ┌───────────────────────────────────────────────────────────────────────┐ │
│ │ ● Submitted · John Doe · Aug 30, 14:00                                │ │
│ │ ● Approved · Jane Smith · Aug 30, 15:30                               │ │
│ │ ○ Pending · HR Manager                                                │ │
│ └───────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────┘
```

**Key characteristics:**
- Timeline is **collapsed by default** (showing only summary)
- Users **expand** to see full history
- **Pro:** Minimal space when collapsed; familiar Bootstrap pattern
- **Con:** Still occupies full width; not visible alongside content

#### Pattern D: Drawer / Slide-Out Panel

**Used by:** GitHub (file history), GitLab (MR changes), Linear (issue activity)

The timeline slides in from the right as an overlay:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ (main content — slightly dimmed)                               [✕]       │
│                                                               ┌─────────┤
│                                                               │Activity │
│                                                               │         │
│                                                               │● Submit │
│                                                               │● Approve│
│                                                               │○ Pending│
│                                                               │         │
│                                                               └─────────┤
└──────────────────────────────────────────────────────────────────────────┘
```

**Key characteristics:**
- Timeline is **hidden by default**, triggered by a button
- **Overlays** content rather than pushing it aside
- **Pro:** Zero layout impact when closed; works well on all screen sizes
- **Con:** Obscures content when open; requires dismiss action

### 4.3 Recommendation: Tab + Optional Drawer

For the PayrollRun detail page, a **dual approach** is recommended:

**Primary: "Activity" Tab**
- Add an "Activity" tab alongside Overview, Payslips, Adjustments, Reconciliation, Audit
- The timeline renders at full width within this tab
- Tab badge shows count of workflow events (e.g., "Activity 3")
- This is the **default** placement — clean, familiar, no layout disruption

**Secondary: Quick-Access Drawer Toggle**
- Add a small "History" icon button in the page header (clock icon)
- Clicking it opens the library's existing [`qf.drawer`](src/Resources/views/livewire/drawer.blade.php) offcanvas with the timeline
- This gives power users **quick access** without leaving their current tab
- The drawer is **supplementary**, not the primary timeline location

```
RECOMMENDED:
┌──────────────────────────────────────────────────────────────────────────┐
│ Payroll Run #PR-2026-008 · January 2026  ✅ Approved  [🕐] [Mark Paid]  │
│ ═══════════════════════════════════════════════════════════════════════ │
│ [Overview] [Payslips] [Adjustments] [Reconciliation] [Audit] [Activity] │
│                                                                          │
│ ┌─ Activity Tab Content ───────────────────────────────────────────────┐ │
│ │ Workflow Timeline                                                     │ │
│ │                                                                       │ │
│ │ ● Submitted · John Doe · Payroll Officer Review · Aug 30, 14:00      │ │
│ │   💬 "Ready for review"                                               │ │
│ │                                                                       │ │
│ │ ● Approved · Jane Smith · HR Manager Authorization · Aug 30, 15:30   │ │
│ │                                                                       │ │
│ │ ○ Pending · HR Manager                                                │ │
│ └───────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Recommended Layout

### 5.1 Complete Page Layout

```
┌──────────────────────────────────────────────────────────────────────────┐
│  TOP BAR (Soft UI Dashboard — fixed)                                     │
│  [☰]  QuickerFaster  │  Payroll  │  HR  │  Time  │  ...  │  [🔔] [👤]   │
├──────────────────────────────────────────────────────────────────────────┤
│  SIDEBAR (fixed, 250px)          │  MAIN CONTENT AREA                    │
│                                  │                                       │
│  Payroll                         │ ┌───────────────────────────────────┐ │
│  ├─ Dashboard                    │ │ PAGE HEADER                       │ │
│  ├─ Pay Runs                     │ │ Payroll Run #PR-2026-008          │ │
│  ├─ Payslips                     │ │ January 2026 · Monthly            │ │
│  ├─ Payroll Approvals  [3]       │ │                                    │ │
│  └─ ...                          │ │ [🕐 History] [📄 Reports ▾]        │ │
│                                  │ │ [💰 Mark Paid] [🔄 Recalculate]    │ │
│  HR                              │ └───────────────────────────────────┘ │
│  ├─ ...                          │                                       │
│                                  │ ┌───────────────────────────────────┐ │
│                                  │ │ APPROVAL BANNER (pending only)    │ │
│                                  │ │ ⚠️ Pending: Step 1 of 2           │ │
│                                  │ │ [✓ Approve] [✗ Reject] [↩ Recall] │ │
│                                  │ │ 👤 Awaiting: J. Smith, A. Jones   │ │
│                                  │ └───────────────────────────────────┘ │
│                                  │                                       │
│                                  │ ┌───────────────────────────────────┐ │
│                                  │ │ TABS                              │ │
│                                  │ │ [Overview] [Payslips] [Adjust.]   │ │
│                                  │ │ [Reconciliation] [Audit]          │ │
│                                  │ │ [Activity 3]                      │ │
│                                  │ ├───────────────────────────────────┤ │
│                                  │ │ TAB CONTENT                       │ │
│                                  │ │ (data tables, metrics, charts)    │ │
│                                  │ │                                   │ │
│                                  │ └───────────────────────────────────┘ │
│                                  │                                       │
└──────────────────────────────────┴───────────────────────────────────────┘
```

### 5.2 Layout Zones

| Zone | Content | Visibility | Source |
|------|---------|------------|--------|
| **Page Header** | Run title, period, status badge, business action buttons (Mark Paid, Recalculate, Reports, History toggle) | Always | Consuming app |
| **Approval Banner** | Status message, Approve/Reject/Recall buttons, awaiting approvers | Only when workflow is pending | `qf.approval-actions` (inline mode) |
| **Tabs** | Overview, Payslips, Adjustments, Reconciliation, Audit, Activity | Always | Consuming app + `qf.approval-history-timeline` in Activity tab |
| **History Drawer** | Timeline (offcanvas, triggered by 🕐 button) | On demand | `qf.drawer` wrapping `qf.approval-history-timeline` |

### 5.3 Responsive Behavior

| Breakpoint | Layout Adaptation |
|------------|-------------------|
| **≥1200px (desktop)** | Full layout as shown. Sidebar visible. Approval banner inline. Activity tab + optional drawer. |
| **768-1199px (tablet)** | Sidebar collapses to icons. Approval banner stacks vertically. Tabs may wrap. Drawer becomes primary timeline access. |
| **<768px (mobile)** | Sidebar hidden (hamburger). Approval banner full-width, buttons stack. Tabs become dropdown or horizontal scroll. Drawer is the only timeline access. |

### 5.4 State Matrix

| Workflow State | Approval Banner | Business Buttons | Activity Tab | History Drawer |
|---------------|-----------------|------------------|--------------|----------------|
| **No workflow** | Hidden | Mark Paid (disabled), Recalculate (enabled), Reports (enabled) | Hidden | Hidden |
| **Pending (user can act)** | **Shown** — warning style, Approve/Reject/Recall enabled | Mark Paid (disabled), Recalculate (disabled), Reports (enabled) | Shown with badge | Available |
| **Pending (user cannot act)** | **Shown** — info style, buttons hidden, "Awaiting others" message | Mark Paid (disabled), Recalculate (disabled), Reports (enabled) | Shown with badge | Available |
| **Approved** | Hidden | Mark Paid (**enabled**), Recalculate (disabled), Reports (enabled) | Shown | Available |
| **Rejected** | Hidden (or dismissed info banner) | Mark Paid (disabled), Recalculate (**enabled**), Reports (enabled) | Shown | Available |
| **Recalled/Cancelled** | Hidden | Mark Paid (disabled), Recalculate (**enabled**), Reports (enabled) | Shown | Available |

---

## 6. Library Component Improvements

### 6.1 Current Limitations

The library's approval components are **functionally complete** but **layout-rigid**:

| Component | Current Limitation |
|-----------|-------------------|
| [`actions.blade.php`](src/Resources/views/livewire/approvals/actions.blade.php) | Only renders as a flat `d-flex` row. No concept of display modes. Consuming app must wrap it in a card, banner, or other container. |
| [`timeline.blade.php`](src/Resources/views/livewire/approvals/timeline.blade.php) | Only renders as a full `list-group`. No concept of compact mode, collapsibility, or drawer integration. |
| Both | No shared state or communication beyond Livewire events (`refreshApprovalActions`, `refreshApprovalTimeline`). |

### 6.2 Recommended Improvements

#### 6.2.1 Approval Actions: Add `displayMode` Property

Add a `displayMode` property to [`ApprovalActions`](src/Http/Livewire/Approvals/ApprovalActions.php) that controls the visual presentation:

| Mode | Description | Use Case |
|------|-------------|----------|
| `inline` (default) | Current behavior — flat `d-flex` row of buttons | Embedding in existing button groups, headers |
| `banner` | Colored alert banner with icon, message, and buttons | Top-of-page approval notification (recommended for PayrollRun) |
| `card` | Full card wrapper with header "Approval Actions" | Standalone placement when no other container exists |

**Implementation approach:**

```php
// ApprovalActions.php — new property
public string $displayMode = 'inline'; // 'inline', 'banner', 'card'
```

The [`actions.blade.php`](src/Resources/views/livewire/approvals/actions.blade.php) view would render different markup based on `$displayMode`:

- **`inline`**: Current behavior (no change)
- **`banner`**: Wraps content in `.alert.alert-warning` with an icon and structured layout
- **`card`**: Wraps content in `.card` with `.card-header` and `.card-body`

**Usage in consuming app:**

```blade
{{-- Banner mode (recommended for PayrollRun) --}}
<livewire:qf.approval-actions 
    :workflow="$run->activeWorkflow" 
    displayMode="banner" 
/>

{{-- Inline mode (for embedding in headers) --}}
<livewire:qf.approval-actions 
    :workflow="$run->activeWorkflow" 
    displayMode="inline" 
/>
```

#### 6.2.2 Approval History Timeline: Add `displayMode` Property

Add a `displayMode` property to [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php):

| Mode | Description | Use Case |
|------|-------------|----------|
| `full` (default) | Current behavior — full list-group with all details | Standalone card, dedicated Activity tab |
| `compact` | Condensed list without comments, smaller avatars | Sidebar, drawer, or space-constrained areas |
| `steps-only` | Horizontal step indicator (like a progress bar) | Page header, quick status overview |

**Implementation approach:**

```php
// ApprovalHistoryTimeline.php — new property
public string $displayMode = 'full'; // 'full', 'compact', 'steps-only'
```

**`compact` mode** would render:
- Smaller avatars (20px instead of 28px)
- No comment blocks (only the action label, actor, and timestamp)
- Tighter spacing between items
- Suitable for the offcanvas drawer

**`steps-only` mode** would render a horizontal step progress indicator:

```
Payroll Officer Review ─── HR Manager Authorization ─── Complete
        ✅                          ○
```

This is useful as a compact status indicator in the page header.

#### 6.2.3 New: Combined Approval Panel Component

Create a new `qf.approval-panel` component that combines actions + timeline into a single, cohesive unit:

```php
// src/Http/Livewire/Approvals/ApprovalPanel.php
class ApprovalPanel extends Component
{
    public ?int $workflowId = null;
    public string $layout = 'sidebar'; // 'sidebar', 'banner-with-drawer', 'tab'
}
```

**`sidebar` layout:**
```
┌──────────────────────────────┬──────────────────────┐
│                              │  Approval Panel       │
│  (main content — passed      │                      │
│   via slot)                  │  Status: Pending      │
│                              │  [✓ Approve]          │
│                              │  [✗ Reject]           │
│                              │  [↩ Recall]           │
│                              │                      │
│                              │  ── Progress ──       │
│                              │  ✅ Step 1            │
│                              │  ○ Step 2             │
│                              │                      │
│                              │  ── History ──        │
│                              │  ● Submitted          │
│                              │  ● Approved           │
└──────────────────────────────┴──────────────────────┘
```

**`banner-with-drawer` layout:**
- Approval actions in a top banner
- Timeline in a slide-out drawer (triggered by button in banner)
- Main content gets full width

**`tab` layout:**
- Approval actions in a banner
- Timeline in a dedicated tab
- Main content in other tabs

This component would be the **highest-level abstraction** — consuming apps drop it in and get a complete approval UX without composing individual components.

#### 6.2.4 Timeline: Add `collapsible` Property

Add a simple `collapsible` boolean to [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php):

```php
public bool $collapsible = false;
public bool $initiallyOpen = true;
```

When `collapsible` is true, the timeline wraps in the library's existing [`qf.collapsible`](src/Resources/views/livewire/collapsible.blade.php) pattern with a toggle header.

#### 6.2.5 Timeline: Add `maxItems` Property

Add a `maxItems` property to limit the number of visible events:

```php
public ?int $maxItems = null; // null = show all, 5 = show last 5
public bool $showViewAll = true; // show "View all N events" link when truncated
```

This prevents the timeline from becoming excessively long for workflows with many steps or resubmissions.

### 6.3 Summary of Recommended Library Changes

| Change | Component | Priority | Effort |
|--------|-----------|----------|--------|
| Add `displayMode` (`inline`/`banner`/`card`) | [`ApprovalActions`](src/Http/Livewire/Approvals/ApprovalActions.php) + [`actions.blade.php`](src/Resources/views/livewire/approvals/actions.blade.php) | 🔴 High | Small |
| Add `displayMode` (`full`/`compact`/`steps-only`) | [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) + [`timeline.blade.php`](src/Resources/views/livewire/approvals/timeline.blade.php) | 🔴 High | Small |
| Add `collapsible` + `initiallyOpen` | [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) + [`timeline.blade.php`](src/Resources/views/livewire/approvals/timeline.blade.php) | 🟡 Medium | Small |
| Add `maxItems` + `showViewAll` | [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) + [`timeline.blade.php`](src/Resources/views/livewire/approvals/timeline.blade.php) | 🟡 Medium | Small |
| Create `ApprovalPanel` component | New file: `src/Http/Livewire/Approvals/ApprovalPanel.php` + view | 🟢 Lower | Medium |

---

## 7. Implementation Roadmap

### Phase 1: Library Component Improvements (Library) ✅ COMPLETE

1. ✅ Add `displayMode` to [`ApprovalActions`](src/Http/Livewire/Approvals/ApprovalActions.php) with `banner` mode support
2. ✅ Add `displayMode` to [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) with `compact` mode support
3. ⚠️ `collapsible` and `maxItems` deferred — not needed for current use cases

### Phase 2: PayrollRun Detail Page Restructure (Consuming App) ✅ COMPLETE

1. ✅ Replaced the full-width `qf.approval-actions` card with `displayMode="banner"` via [`ApprovalPanel`](src/Http/Livewire/Approvals/ApprovalPanel.php)
2. ✅ Moved business buttons (Mark Paid, Recalculate, Reports) to the page header
3. ✅ Added "Activity" tab containing `qf.approval-history-timeline`
4. ✅ Added History drawer toggle button in page header using [`qf.drawer`](src/Resources/views/livewire/drawer.blade.php)
5. ✅ Removed old inline Approve/Cancel button handlers (now handled by library)
6. ✅ Gated Mark Paid on workflow completion; gated Recalculate on draft/recalled state

### Phase 3: Combined Panel (Library) ✅ COMPLETE

1. ✅ Created [`ApprovalPanel`](src/Http/Livewire/Approvals/ApprovalPanel.php) component
2. ✅ Supports `banner`, `card`, and `inline` display modes (mode names simplified from the original `sidebar`/`banner-with-drawer`/`tab` proposal — the `banner` mode includes a drawer-accessible timeline, `card` wraps both actions and timeline in a card, and `inline` renders a flat row)
3. ✅ Registered as `qf.approval-panel` in [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php)

### Implementation Notes

- **Mode name deviation**: The original plan proposed `sidebar`, `banner-with-drawer`, and `tab` layouts. The actual implementation uses `banner`, `card`, and `inline` — simpler names that better describe the visual presentation.
- **Button contrast**: Banner mode buttons use `btn-light` + `text-success`/`text-danger`/`text-dark` for proper contrast on colored alert backgrounds.
- **Recall button**: Changed from `wire:confirm` to `openCommentModal('recall')` for consistency with Approve/Reject.
- **Status filter fixes**: [`ApprovalRequestListView`](src/Http/Livewire/Approvals/ApprovalRequestListView.php) received fixes for conflicting WHERE clauses, "All statuses" option, initial default, and reactive title.
- **`effectiveStatus()` helper**: Added to PayrollRun to resolve display status considering both business and workflow state.
- **Stale overrides deleted**: Removed vendor-published `top-nav.blade.php`, `sidebar-item.blade.php`, and `approval-request-list.blade.php` overrides.

---

## Appendix A: Key Files Referenced

### Library Files

| File | Path |
|------|------|
| ApprovalActions (Livewire) | [`src/Http/Livewire/Approvals/ApprovalActions.php`](src/Http/Livewire/Approvals/ApprovalActions.php) |
| ApprovalActions (Blade) | [`src/Resources/views/livewire/approvals/actions.blade.php`](src/Resources/views/livewire/approvals/actions.blade.php) |
| ApprovalHistoryTimeline (Livewire) | [`src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) |
| ApprovalHistoryTimeline (Blade) | [`src/Resources/views/livewire/approvals/timeline.blade.php`](src/Resources/views/livewire/approvals/timeline.blade.php) |
| ApprovalRequestListView | [`src/Http/Livewire/Approvals/ApprovalRequestListView.php`](src/Http/Livewire/Approvals/ApprovalRequestListView.php) |
| Drawer component | [`src/Resources/views/livewire/drawer.blade.php`](src/Resources/views/livewire/drawer.blade.php) |
| Collapsible component | [`src/Resources/views/livewire/collapsible.blade.php`](src/Resources/views/livewire/collapsible.blade.php) |
| App layout | [`src/Resources/views/layouts/app.blade.php`](src/Resources/views/layouts/app.blade.php) |
| WorkflowEngine | [`src/Services/Workflow/WorkflowEngine.php`](src/Services/Workflow/WorkflowEngine.php) |
| ApprovalGuard | [`src/Services/Approvals/ApprovalGuard.php`](src/Services/Approvals/ApprovalGuard.php) |

### Consuming App Files

| File | Path |
|------|------|
| PayrollRun detail Blade | `app/Modules/Payroll/Resources/views/payroll-runs/show.blade.php` |
| PayrollRunDetail Livewire | `app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php` |
| PayrollRun model | `app/Modules/Payroll/Models/PayrollRun.php` |
| Payroll workflow config | `app/Modules/Payroll/Config/workflows.php` |
| Old backup (reference) | `/Users/mac/Projects/Libraries/modules backup/Modules/Hr/Resources/views/livewire/payroll/payroll-run-detail.blade.php` |

### Plan Files

| File | Path |
|------|------|
| Payroll approval implementation plan | [`plans/payroll-approval-implementation-plan.md`](plans/payroll-approval-implementation-plan.md) |
| Horizontal bar UX analysis (precedent) | [`plans/horizontal-bar-sections-ux-analysis.md`](plans/horizontal-bar-sections-ux-analysis.md) |

---

## Appendix B: Design Decisions Log

| Decision | Rationale |
|----------|-----------|
| Approval actions → banner, not card | A full-width card for 3 small buttons wastes space. A colored alert banner communicates status + actions in a compact, scannable format. GitHub/GitLab use this pattern for PR/MR actions. |
| Timeline → Activity tab, not page bottom | A full-width timeline at page bottom forces scrolling and competes with tab content. A dedicated tab follows Jira/ServiceNow patterns and lets users choose when to view history. |
| History drawer as secondary access | Power users want quick timeline access without leaving their current tab. The offcanvas drawer (already in the library) provides this without layout disruption. |
| Business buttons in page header | Mark Paid, Recalculate, and Reports are domain-specific actions unrelated to approval workflow. They belong in the page header with the entity title, following standard enterprise CRUD patterns. |
| `displayMode` on existing components, not new components | Adding a mode property to existing components is lower-effort than creating separate "compact" variants. It keeps the API surface small and lets consuming apps choose the right mode per context. |
| Combined `ApprovalPanel` deferred to Phase 3 | The individual component improvements (`displayMode`) unblock the PayrollRun page immediately. The combined panel is a higher-level abstraction that can be built later once the modes prove themselves in production. |