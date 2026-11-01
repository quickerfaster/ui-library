# Company Policy — UX Test Checklist

> **Purpose**: Manual user-facing verification checklist for each implementation phase.
> **How to use**: Work through each phase sequentially. Check off items as you verify them in the browser.
> **Prerequisites**: 
> - `php artisan migrate` has run on your dev database
> - `php artisan db:seed --class=App\\Modules\\Policies\\Database\\Seeders\\PoliciesRoleSeeder` has run
> - `php artisan optimize:clear` has been executed
> - You are logged in as a user with `hr_manager` role (or `super_admin`/`admin`/`company_admin`)

---

## Phase 1 — Admin CRUD (Foundation)

### 1.1 Module Switcher & Navigation

| # | Check | How to Test | Expected |
|---|---|---|---|
| 1.1.1 | "Policies" appears in top-nav context groups | Log in as hr_manager → select "HR" from module switcher → look at top-nav tabs | "Policies" tab appears between "Onboarding" and "Organization" (or after Onboarding) with gavel icon |
| 1.1.2 | Clicking "Policies" loads the sidebar | Click "Policies" tab in top nav | Sidebar shows: Overview, All Policies, Acknowledgments |
| 1.1.3 | "Overview" sidebar item works | Click "Overview" in sidebar | Loads dashboard with metric widgets (Total Policies, Published, Requiring Ack, Drafts) and Recent Policies list |
| 1.1.4 | "All Policies" sidebar item works | Click "All Policies" in sidebar | Loads DataTable with columns: Title, Category, Version, Status, Effective Date, Requires Ack, Priority |
| 1.1.5 | "Acknowledgments" sidebar item works | Click "Acknowledgments" in sidebar | Loads DataTable with columns: Policy, Employee, Status, Acknowledged At, Signature |
| 1.1.6 | Hardcoded "Policies" top-nav link is gone | Look at the top-nav HTML (the old hardcoded `<li>` with `/hr/dashboard-policies-overview`) | The old hardcoded link is no longer present; only the config-driven context group tab exists |

### 1.2 Create Policy

| # | Check | How to Test | Expected |
|---|---|---|---|
| 1.2.1 | "+" / "Create" button is visible | On "All Policies" DataTable page | Add/Create button is visible (top-right of DataTable) |
| 1.2.2 | Create form opens | Click Create button | Form opens (drawer or page) with fields: Title, Category (dropdown), Version, Status (dropdown), Effective Date (datepicker), Requires Acknowledgment (checkbox), Priority (dropdown), Description (textarea), Content (richtext), Review Date, Acknowledgment Deadline |
| 1.2.3 | Category dropdown has all options | Open Category dropdown | Options: Handbook, Code of Conduct, Leave Policy, Expense Policy, IT Policy, Health & Safety, Other |
| 1.2.4 | Status dropdown has correct options | Open Status dropdown | Options: Draft, Published, Archived |
| 1.2.5 | Priority dropdown has correct options | Open Priority dropdown | Options: Low, Medium, High, Critical |
| 1.2.6 | Can create a draft policy | Fill in Title + Category, leave Status as "Draft", click Save | Policy appears in DataTable with Status = "Draft" |
| 1.2.7 | Can create a published policy | Fill all required fields, set Status = "Published", click Save | Policy appears with Status = "Published" |
| 1.2.8 | Validation works | Try creating a policy with empty Title | Validation error: "Title is required" |
| 1.2.9 | Default values are correct | Create a policy with only Title + Category | Version defaults to "1.0", Status defaults to "Draft", Priority defaults to "Medium", Requires Ack defaults to unchecked |

### 1.3 Edit Policy

| # | Check | How to Test | Expected |
|---|---|---|---|
| 1.3.1 | Edit button works | Click a policy row or edit icon | Edit form opens with pre-filled values |
| 1.3.2 | Can update policy fields | Change Title, Category, etc., click Save | Changes are saved and reflected in DataTable |
| 1.3.3 | Can change status | Change a Draft to Published | Status updates correctly |

### 1.4 Row Actions (moreActions)

| # | Check | How to Test | Expected |
|---|---|---|---|
| 1.4.1 | "Publish" action appears on Draft policies | Find a Draft policy → click "..." more actions | "Publish" option with check-circle icon is visible |
| 1.4.2 | "Publish" action does NOT appear on Published policies | Find a Published policy → click "..." more actions | "Publish" is NOT in the dropdown |
| 1.4.3 | "Archive" action appears on Published policies | Find a Published policy → click "..." more actions | "Archive" option with archive icon is visible |
| 1.4.4 | "Archive" action does NOT appear on Draft policies | Find a Draft policy → click "..." more actions | "Archive" is NOT in the dropdown |

### 1.5 Delete Policy

| # | Check | How to Test | Expected |
|---|---|---|---|
| 1.5.1 | Can delete a policy | Click delete on a policy row | Confirmation prompt → policy is soft-deleted (removed from list) |

### 1.6 Filters & Search

| # | Check | How to Test | Expected |
|---|---|---|---|
| 1.6.1 | Search by title works | Type part of a policy title in search box | DataTable filters to matching policies |
| 1.6.2 | Category filter works | Select a category from filter dropdown | DataTable shows only that category |
| 1.6.3 | Status filter works | Select "Draft" or "Published" from status filter | DataTable filters correctly |
| 1.6.4 | Clear filters works | Apply filters, then clear them | All policies shown again |

---

## Phase 2 — ESS Policy Hub

### 2.1 Navigation

> **Prerequisite**: Log in as an `employee` role user (not admin/HR)

| # | Check | How to Test | Expected |
|---|---|---|---|
| 2.1.1 | "Company Policies" appears in My Portal sidebar | Log in as employee → HR module → My Portal | "Company Policies" appears in sidebar with gavel icon, after "Team Calendar" |
| 2.1.2 | Clicking loads the policy hub | Click "Company Policies" | Page loads with "Company Policies" heading, search bar, category filter dropdown |

### 2.2 Policy Display

> **Prerequisite**: Create at least 2 published policies (one requiring acknowledgment, one not) via admin

| # | Check | How to Test | Expected |
|---|---|---|---|
| 2.2.1 | Policies requiring acknowledgment appear in "Pending" section | View policy hub as employee | Yellow-bordered cards in "Pending Acknowledgment" section with warning icon and count badge |
| 2.2.2 | Policies NOT requiring acknowledgment appear in "Acknowledged & Information" section | View policy hub | Green-bordered cards in lower section |
| 2.2.3 | Card shows correct info | Look at a policy card | Shows: title, category badge, version, effective date, description (truncated to 120 chars) |
| 2.2.4 | Pending cards have "Read & Acknowledge" button | Look at a pending policy card | Yellow "Read & Acknowledge" button is visible |
| 2.2.5 | Acknowledged cards have "View" button | Look at an acknowledged/info policy card | Green outline "View" button is visible |

### 2.3 Search & Filter

| # | Check | How to Test | Expected |
|---|---|---|---|
| 2.3.1 | Search filters policies | Type part of a policy title in search box | Only matching policies shown (both sections update) |
| 2.3.2 | Category filter works | Select a category from dropdown | Only policies in that category shown |
| 2.3.3 | Empty state shows when no results | Search for "zzz_nonexistent" | "No policies found" message with suggestion to adjust filters |
| 2.3.4 | Empty state shows when no policies exist | (On fresh DB with no published policies) | "No company policies have been published yet" message |

### 2.4 Acknowledgment Flow

| # | Check | How to Test | Expected |
|---|---|---|---|
| 2.4.1 | Clicking "Read & Acknowledge" opens modal | Click the button on a pending policy | Modal opens with policy title, category badge, version, priority, description summary, full content, signature field |
| 2.4.2 | Modal shows full policy content | Look at modal body | Policy content (HTML) is rendered in the scrollable content area |
| 2.4.3 | "I Acknowledge" button is disabled without signature | Look at modal footer | "I Acknowledge" button is grayed out / disabled when signature field is empty |
| 2.4.4 | Typing name enables the button | Type something in signature field | "I Acknowledge" button becomes enabled |
| 2.4.5 | Acknowledging moves card to acknowledged section | Type name → click "I Acknowledge" | Modal closes → card moves from yellow "Pending" to green "Acknowledged" section |
| 2.4.6 | Acknowledged card shows "View" button instead | Look at the acknowledged card | Button now says "View" (green outline) instead of "Read & Acknowledge" (yellow) |
| 2.4.7 | Closing modal without acknowledging does nothing | Open modal → click Close or backdrop | Modal closes, policy remains in Pending section |
| 2.4.8 | Cannot acknowledge twice | Try clicking "View" on an already-acknowledged policy → modal opens but shows content only | No duplicate acknowledgment created |

### 2.5 Modal Content Display

| # | Check | How to Test | Expected |
|---|---|---|---|
| 2.5.1 | Policy with HTML content renders correctly | Create a policy with `<p>`, `<ul>`, `<strong>` tags in content → view in modal | HTML renders properly (not escaped) |
| 2.5.2 | Policy without content shows PDF message | Create a policy with empty content → view in modal | "Policy content is available as a PDF document" message with PDF icon |
| 2.5.3 | Critical/High priority shows red badge | Create a policy with priority "critical" → view in modal | Red "CRITICAL" badge next to category/version badges |

---

## Phase 3 — Acknowledgment Tracking & Compliance

### 3.1 Acknowledgment Report

> **Prerequisite**: At least one employee has acknowledged at least one policy

| # | Check | How to Test | Expected |
|---|---|---|---|
| 3.1.1 | Acknowledgments page loads | Log in as hr_manager → HR → Policies → Acknowledgments | DataTable with acknowledgment records |
| 3.1.2 | Columns are correct | Look at table columns | Policy (title + version hint), Employee (employee_number + name hint), Status, Acknowledged At, Signature |
| 3.1.3 | Status filter works | Filter by "Acknowledged" or "Pending" | Table filters correctly |
| 3.1.4 | Policy filter works | Filter by a specific policy | Only acknowledgments for that policy shown |
| 3.1.5 | No create/edit/delete buttons | Look at the page | No Add button, rows are not editable, no delete actions (read-only report) |
| 3.1.6 | Employee search works | Search for an employee number or name | Matching records shown |

### 3.2 Compliance Dashboard

| # | Check | How to Test | Expected |
|---|---|---|---|
| 3.2.1 | Dashboard loads | HR → Policies → Overview | Dashboard with widgets |
| 3.2.2 | "Total Policies" metric is correct | Compare with count in "All Policies" table | Number matches |
| 3.2.3 | "Published" metric is correct | Count published policies | Number matches |
| 3.2.4 | "Requiring Acknowledgment" metric is correct | Count published policies with requires_acknowledgment = true | Number matches |
| 3.2.5 | "Drafts" metric is correct | Count draft policies | Number matches |
| 3.2.6 | "Recent Policies" list shows latest 5 | Compare with "All Policies" sorted by created_at desc | Top 5 most recent policies shown |

---

## Phase 4 — Onboarding Integration

### 4.1 Onboarding Wizard

> **Prerequisite**: 
> - At least one published policy with `requires_acknowledgment = true` and no distribution scope (applies to all)
> - A new employee account that hasn't completed onboarding
> - Log in as that new employee

| # | Check | How to Test | Expected |
|---|---|---|---|
| 4.1.1 | Step 6 appears in wizard | Start onboarding wizard → navigate through steps | After Step 5 (Preferences), Step 6 "Company Policies" appears |
| 4.1.2 | Step shows mandatory policies | Look at Step 6 content | Cards for each mandatory policy with title, category badge, version, priority |
| 4.1.3 | "Read & Acknowledge" button on each unacknowledged policy | Look at policy cards | Yellow "Read & Acknowledge" button on each |
| 4.1.4 | Clicking opens modal | Click "Read & Acknowledge" | Modal with full policy content + signature field |
| 4.1.5 | Acknowledging updates card | Type name → click "I Acknowledge" | Card shows green "Acknowledged" badge with check icon |
| 4.1.6 | "All acknowledged" message appears | Acknowledge all mandatory policies | Green alert: "All required policies have been acknowledged. You can proceed to the next step." |
| 4.1.7 | Step is skippable | Click "Skip" button in wizard | Step is skipped, wizard proceeds |
| 4.1.8 | Empty state when no mandatory policies | (Remove all mandatory policies) → view Step 6 | "No policies require your acknowledgment at this time. You can skip this step." |
| 4.1.9 | Step remembers acknowledgments on navigation | Acknowledge a policy → navigate to Step 5 → back to Step 6 | Previously acknowledged policy still shows as acknowledged |

---

## Phase 5 — Notifications & Reminders

### 5.1 Notification Templates

> **Prerequisite**: Run `php artisan db:seed --class=App\\Modules\\Policies\\Database\\Seeders\\PolicyNotificationTemplateSeeder`

| # | Check | How to Test | Expected |
|---|---|---|---|
| 5.1.1 | Templates exist in database | Check `notification_templates` table for types: `policy_published`, `policy_updated`, `policy_acknowledgment_reminder`, `policy_acknowledgment_overdue` | 8 rows (4 types × 2 channels: database + mail) |
| 5.1.2 | Database channel templates have correct subjects | Query the database | Subjects contain `{policy_title}` placeholder |
| 5.1.3 | Mail channel templates have correct body | Query the database | Body contains `{employee_name}`, `{policy_title}`, `{policy_url}` placeholders |

### 5.2 Console Command

| # | Check | How to Test | Expected |
|---|---|---|---|
| 5.2.1 | Command is registered | Run `php artisan list` | `policies:send-reminders` appears in the command list |
| 5.2.2 | Dry-run works with no policies | Run `php artisan policies:send-reminders --dry-run` | "No policies with acknowledgment deadlines found." |
| 5.2.3 | Dry-run shows what would be sent | Create a published policy with `requires_acknowledgment = true` and `acknowledgment_required_by = tomorrow` → run `php artisan policies:send-reminders --dry-run` | Shows "[DRY RUN] Would send..." for each unacknowledged employee |
| 5.2.4 | `--days-before` option works | Run `php artisan policies:send-reminders --days-before=30 --dry-run` | Wider window catches more policies |
| 5.2.5 | Overdue detection works | Create a policy with `acknowledgment_required_by = yesterday` → run dry-run | Shows "[OVERDUE]" label for past-deadline policies |

---

## Cross-Phase Integration Checks

### Navigation Consistency

| # | Check | How to Test | Expected |
|---|---|---|---|
| C.1 | HR module context groups are correct | Log in as hr_manager → HR module | Context groups: Dashboard, My Portal, Organization, People, Manage, Onboarding, **Policies** |
| C.2 | My Portal context items are correct | Log in as employee → HR → My Portal | Sidebar: Overview, My Profile, Leave, Team Calendar, **Company Policies** |
| C.3 | Policies context items are correct | Log in as hr_manager → HR → Policies | Sidebar: Overview, All Policies, Acknowledgments |
| C.4 | No broken links | Click every sidebar item in Policies context | All pages load without errors |
| C.5 | Active state highlighting works | Navigate between context groups and sidebar items | Current page is highlighted in sidebar |

### Permission Checks

| # | Check | How to Test | Expected |
|---|---|---|---|
| P.1 | Employee cannot see admin Policies context group | Log in as employee → HR module | "Policies" does NOT appear in top-nav context groups |
| P.2 | Employee CAN see Company Policies in My Portal | Log in as employee → HR → My Portal | "Company Policies" appears in sidebar |
| P.3 | hr_manager CAN see Policies context group | Log in as hr_manager → HR module | "Policies" appears in top-nav context groups |
| P.4 | Unauthenticated user cannot access policy pages | Log out → navigate to `/hr/company-policies` | Redirected to login |

### Mobile/Responsive

| # | Check | How to Test | Expected |
|---|---|---|---|
| M.1 | Policy cards stack on mobile | Resize browser to < 768px width | Cards stack vertically (col-12) |
| M.2 | Search and filter wrap on mobile | Resize browser to < 576px | Search bar and filter dropdown stack vertically |
| M.3 | Modal is scrollable on mobile | Open acknowledgment modal on small screen | Modal content scrolls, buttons remain accessible |
| M.4 | Bottom bar shows on mobile | View on mobile width | Bottom navigation bar is visible |

---

## Quick Smoke Test (All Phases)

Run through this 5-minute sequence to verify end-to-end:

1. **Admin**: Log in as hr_manager → HR → Policies → All Policies → Create a policy titled "Test Policy" with category "Handbook", status "Published", requires_acknowledgment = true, acknowledgment deadline = next week
2. **Admin**: Verify policy appears in DataTable with correct values
3. **Admin**: Go to Overview → verify dashboard metrics updated
4. **ESS**: Log out → log in as employee → HR → My Portal → Company Policies
5. **ESS**: Verify "Test Policy" appears in Pending Acknowledgment section
6. **ESS**: Click "Read & Acknowledge" → read content → type name → click "I Acknowledge"
7. **ESS**: Verify card moves to Acknowledged section
8. **Admin**: Log out → log in as hr_manager → HR → Policies → Acknowledgments
9. **Admin**: Verify employee's acknowledgment record appears with correct status, timestamp, and signature
10. **CLI**: Run `php artisan policies:send-reminders --dry-run` → verify command runs without errors