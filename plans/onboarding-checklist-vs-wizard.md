# Onboarding: Checklist vs Wizard — Comparative Analysis

## 1. Checklist Approach — Detailed Design

### 1.1 Concept

A single page at `/onboarding` displays a vertical list of onboarding tasks. Each task represents a model that needs data (Employee, EmployeeProfile, etc.). Clicking a task opens the library's existing [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php) inside the library's existing [`Drawer`](src/Http/Livewire/Drawer.php) (Bootstrap offcanvas). When the user saves the form, the drawer closes and the task is marked complete. Required tasks must be finished before the "Proceed to Dashboard" button becomes active. Optional tasks can be explicitly skipped.

### 1.2 Architecture

```
┌──────────────────────────────────────────────────────────────┐
│  Onboarding Checklist                          Progress: 40% │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ ✓  Employee Record                        COMPLETED    │  │
│  │    Set up your basic employment information            │  │
│  │    [Click to review]                                   │  │
│  ├────────────────────────────────────────────────────────┤  │
│  │ ○  Employee Profile                        OPTIONAL    │  │
│  │    Add personal details and emergency contacts         │  │
│  │    [Complete]    [Skip for Now]                        │  │
│  ├────────────────────────────────────────────────────────┤  │
│  │ ○  Payroll & Banking                      OPTIONAL    │  │
│  │    Set up bank details for salary payments             │  │
│  │    [Complete]    [Skip for Now]                        │  │
│  ├────────────────────────────────────────────────────────┤  │
│  │ ○  Documents                              OPTIONAL    │  │
│  │    Upload ID, certificates, and documents              │  │
│  │    [Complete]    [Skip for Now]                        │  │
│  ├────────────────────────────────────────────────────────┤  │
│  │ ○  Notification Preferences               OPTIONAL    │  │
│  │    Choose how you want to be notified                  │  │
│  │    [Complete]    [Skip for Now]                        │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │  [Proceed to Dashboard]  (disabled: 1 required task    │  │
│  │   remaining)                                           │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌─ Drawer (offcanvas, slides in from right) ─────────────┐  │
│  │                                                        │  │
│  │  Employee Profile                                      │  │
│  │  ┌──────────────────────────────────────────────────┐  │  │
│  │  │  <livewire:qf.data-table-form                    │  │  │
│  │  │      configKey="hr.employee_profiles"            │  │  │
│  │  │      :prefilledData="{ employee_id: 42 }"        │  │  │
│  │  │      crudType="drawers" />                       │  │  │
│  │  └──────────────────────────────────────────────────┘  │  │
│  │                                                        │  │
│  │  [Save]  [Cancel]                                      │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### 1.3 Component Structure

| # | File | Purpose |
|---|------|---------|
| 1 | `app/Modules/Hr/Http/Livewire/Onboarding/OnboardingChecklist.php` | **Single Livewire component** managing the checklist state, drawer orchestration, skip tracking, and completion gating |
| 2 | `app/Modules/Hr/Resources/views/onboarding/checklist.blade.php` | **Single Blade view** rendering the task list with status indicators and the global drawer trigger |
| 3 | `app/Modules/Hr/Config/onboarding.php` | Config defining each task: `model`, `configKey` (for DataTableForm), `condition`, `required` flag, `icon`, `title`, `description` |

The library's existing components are reused **without modification**:

| Library Component | Role |
|-------------------|------|
| [`qf.data-table-form`](src/Http/Livewire/DataTables/DataTableForm.php) | Form for each model, opened in the drawer |
| [`qf.drawer`](src/Http/Livewire/Drawer.php) | Global offcanvas drawer hosting the DataTableForm |
| [`OnboardingCondition`](src/Contracts/OnboardingCondition.php) contract | Same `__invoke($user): bool` interface for checking task completion |

### 1.4 How It Works — Step by Step

1. **User lands at `/onboarding`**. The `OnboardingChecklist` Livewire component loads.
2. **Component reads the consolidated step config** (same 5-step structure as the wizard plan, but expressed as tasks rather than sequential steps).
3. **Each task's condition is evaluated** via `OnboardingCondition::__invoke($user)`. Results map to statuses: `complete`, `pending`, `skipped`.
4. **User clicks a pending task's "Complete" button**. The component dispatches a `drawerOpened` event with the task's `configKey` and any `prefilledData`.
5. **The global [`Drawer`](src/Http/Livewire/Drawer.php) mounts the [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php)** with the specified `configKey`. The `DataTableForm` handles all CRUD — field rendering, validation, file upload, save — exactly as it does everywhere else in the system.
6. **User saves the form**. `DataTableForm` emits a `DataTableRecordSaved` event. The `OnboardingChecklist` component listens for this, re-evaluates the condition, and marks the task complete.
7. **User skips an optional task**. The component stores `skippedTasks` in its Livewire state. The condition is treated as satisfied.
8. **When all required tasks are complete**, the "Proceed to Dashboard" button activates. Clicking it marks the Spatie Onboard step as complete and redirects to `/home`.

### 1.5 OnboardingChecklist Livewire Component (Conceptual)

```php
class OnboardingChecklist extends Component
{
    public array $tasks = [];           // Loaded from config
    public array $taskStatuses = [];    // 'pending' | 'complete' | 'skipped'
    public array $skippedTasks = [];    // Task keys explicitly skipped
    public ?string $activeTaskKey = null;

    public function mount(): void
    {
        $this->tasks = config('hr_onboarding.employee_onboarding.steps');
        $this->evaluateAllTasks();
    }

    public function openTask(string $taskKey): void
    {
        $task = $this->tasks[$taskKey];
        $this->activeTaskKey = $taskKey;

        $this->dispatch('openDrawer', component: 'qf.data-table-form', params: [
            'configKey' => $task['configKey'],
            'prefilledData' => $this->getPrefilledData($taskKey),
            'crudType' => 'drawers',
        ]);
    }

    public function skipTask(string $taskKey): void
    {
        if ($this->tasks[$taskKey]['required']) {
            return; // Cannot skip required tasks
        }
        $this->skippedTasks[] = $taskKey;
        $this->taskStatuses[$taskKey] = 'skipped';
    }

    #[On('dataTableRecordSaved')]
    public function onRecordSaved(): void
    {
        // Re-evaluate the active task's condition
        $condition = app($this->tasks[$this->activeTaskKey]['condition']);
        if ($condition(auth()->user())) {
            $this->taskStatuses[$this->activeTaskKey] = 'complete';
        }
        $this->dispatch('closeDrawer');
        $this->activeTaskKey = null;
    }

    public function proceedToDashboard(): void
    {
        // Mark Spatie Onboard step complete
        auth()->user()->onboarding()->completeCurrentStep();
        return redirect()->to('/home');
    }

    public function get canProceed(): bool
    {
        foreach ($this->tasks as $key => $task) {
            if ($task['required'] && $this->taskStatuses[$key] !== 'complete') {
                return false;
            }
        }
        return true;
    }
}
```

### 1.6 Task Config (Example)

```php
// onboarding.php — checklist variant
'employee_onboarding' => [
    'tasks' => [
        [
            'key' => 'employee_record',
            'title' => 'Employee Record',
            'description' => 'Set up your basic employment information',
            'icon' => 'fa-id-card',
            'model' => \App\Modules\Hr\Models\Employee::class,
            'configKey' => 'hr.employees',           // DataTableForm config key
            'condition' => \App\Modules\Hr\Conditions\EmployeeRecordCreated::class,
            'required' => true,
        ],
        [
            'key' => 'employee_profile',
            'title' => 'Employee Profile',
            'description' => 'Add personal details and emergency contacts',
            'icon' => 'fa-user',
            'model' => \App\Modules\Hr\Models\EmployeeProfile::class,
            'configKey' => 'hr.employee_profiles',
            'condition' => \App\Modules\Hr\Conditions\EmployeeProfileComplete::class,
            'required' => false,
        ],
        // ... payroll_banking, documents, preferences
    ],
],
```

---

## 2. Strengths & Weaknesses Comparison

| Dimension | Wizard | Checklist |
|-----------|--------|-----------|
| **Implementation effort** | **High.** Requires creating 7 new files (1 master component, 1 main view, 5 step partials, 1 step indicator partial) plus rewriting config, service provider, and routes. Must build custom form UI for each of 5 steps. Must implement step navigation, progress tracking, skip persistence, and responsive layout from scratch. | **Low.** Requires 2 new files (1 master component, 1 Blade view) plus config updates. All form rendering is delegated to the existing [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php) (1,478 lines of battle-tested code). The existing [`Drawer`](src/Http/Livewire/Drawer.php) handles the offcanvas UX. No new form logic, validation, field rendering, or file upload code. |
| **Code reuse (existing DataTableForm)** | **Minimal.** The wizard plan creates custom form partials for each step. It does plan to use [`WizardForm`](src/Http/Livewire/Wizards/WizardForm.php) for steps that map to a model config key, but the plan's 5 step partials suggest custom UI per step. The wizard's step 4 (Documents) and step 5 (Preferences) would need entirely custom form handling since they don't fit the single-model DataTableForm pattern. | **Maximum.** Every task that maps to a model opens the exact same [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php) that the rest of the application uses. The checklist component is a thin orchestrator — it delegates all form rendering, validation, file upload, and record persistence to the existing infrastructure. The only "new" code is the checklist state machine and drawer orchestration. |
| **User guidance** | **Strong.** The wizard enforces a linear narrative. The user is guided step-by-step with a progress bar, step titles, and "Step X of Y" context. The required-first ordering prevents the user from being overwhelmed with optional tasks before completing the essential one. The step indicator shows exactly where they are in the journey. | **Weaker.** The checklist presents all tasks at once, which can be overwhelming for a first-time user. There is no inherent "do this first" guidance beyond the visual ordering. The user must decide what to tackle. The "Proceed to Dashboard" button being disabled provides a clear signal about what's required, but the checklist itself doesn't guide the user through the tasks in any particular order. |
| **Flexibility (non-linear)** | **Rigid.** The wizard enforces a strict sequential flow. Users cannot jump ahead to fill out documents before completing the employee profile. While this is good for guidance, it frustrates users who want to complete tasks in their preferred order. The wizard plan allows clicking back to skipped steps, but forward navigation is always sequential. | **High.** Users can tackle tasks in any order they prefer. Someone who wants to set up bank details first can do so. Someone who wants to skip the profile and come back to it later can. The checklist is non-judgmental about task ordering. This matches how many users actually work — they scan the list, pick the easiest or most interesting task, and work from there. |
| **Mobile responsiveness** | **Moderate.** The wizard plan's side-by-side layout (step indicator left, content right) requires responsive breakpoints to collapse to a stacked layout on mobile. The vertical step indicator becomes horizontal step dots on small screens. This is a proven pattern but requires careful CSS and testing. | **Good.** The checklist is inherently mobile-friendly — a vertical stack of task cards with a simple linear layout. The drawer slides in from the right with full-width on mobile, which Bootstrap's offcanvas handles natively. No complex responsive breakpoints needed beyond what the library already provides. |
| **Revisit-ability** | **Limited.** The wizard plan allows clicking back to skipped steps from the completion screen (open question #2). However, once the wizard is finished, the user must navigate to the model's regular CRUD pages (e.g., `/hr/employees/42/edit`) to make changes. There is no "return to onboarding" path. | **Natural.** The checklist page can be revisited at any time. It always shows the current state of all tasks. If a user completes onboarding, then later wants to add documents, they can navigate back to `/onboarding` and see the Documents task as "complete" with an option to "review" or "update." The task list doubles as a persistent onboarding status dashboard. |
| **Dependency between steps** | **Enforced.** The wizard plan explicitly gates step 1 (Employee Record) before optional steps unlock. This is correct for the requirement that Employee must exist before other models can be created (since EmployeeProfile, EmployeePayrollProfile, and Documents all have foreign keys to Employee). | **Must be managed.** The checklist must handle the dependency explicitly: if the user clicks "Payroll & Banking" before creating an Employee record, the form will fail because `employee_id` can't be null. The checklist component needs to either (a) pre-create the Employee record automatically, (b) disable dependent tasks until the prerequisite is met, or (c) show an error message guiding the user to complete the prerequisite first. This is the checklist's single biggest design challenge. |
| **Adding/removing tasks** | **Moderate effort.** Adding a new step requires: (1) a new Blade partial, (2) new handler methods in the wizard component, (3) a config entry. The wizard component must be modified for each new step because it conditionally renders specific partials. Removing a step requires deleting the partial and the component methods. | **Trivial.** Adding a new task requires one config entry (key, title, icon, configKey, condition, required flag). The checklist component is generic — it iterates over the config array and renders each task uniformly. No code changes to the component. This is the same configurability model that drives `DataTableForm` itself. |
| **First-time user experience** | **Excellent.** The wizard provides a polished, guided experience. The user never feels lost. The progress bar gives a sense of accomplishment. The "Skip for Now" button is clearly visible on optional steps. The completion screen provides a celebratory moment. This is the approach used by Gusto, Notion, and most modern SaaS products for onboarding. | **Adequate.** The checklist is functional but less polished. It feels more like a task management tool than a guided onboarding experience. The user sees all tasks at once, which can feel like a to-do list rather than a journey. However, the "Proceed to Dashboard" button with a clear disabled state communicates the goal clearly. |
| **Maintenance burden** | **High.** 7 new files to maintain. Custom form UI for each step duplicates what `DataTableForm` already does. If a field definition changes in the model config, the wizard partial must be updated separately. The wizard component has 5+ step-specific methods. Bug fixes to form behavior (e.g., validation, file upload, auto-generate) must be applied to the wizard's custom form code as well as the existing `DataTableForm`. | **Low.** 2 new files. The checklist component is a thin orchestrator (~150 lines). All form behavior is maintained in one place: [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php). A bug fix to form rendering benefits the checklist automatically. A new field type added to the system works in the checklist automatically. The checklist is a consumer of the library's form infrastructure, not a competitor to it. |

---

## 3. Competitive Analysis

### 3.1 Gusto — Employee Self-Onboarding

**Approach**: Wizard (multi-step form)

Gusto's employee self-onboarding is a classic wizard. After receiving an invitation, the employee steps through:
1. Personal Info (name, DOB, SSN)
2. Contact Details (address, phone)
3. Tax Withholding (W-4 equivalent)
4. Bank Details (direct deposit)
5. Document Upload (I-9 verification)
6. Review & Submit

**Why a wizard**: Gusto's onboarding is compliance-heavy. Each step has legal implications (tax forms, I-9 verification). The sequential wizard ensures nothing is missed. The "Review & Submit" step provides a final checkpoint before data is locked in. Gusto also offers a "Save & Continue Later" option, allowing users to pause mid-wizard.

**Key UX patterns**:
- Progress bar at the top showing overall completion
- "Save & Continue" on each step (auto-save to draft)
- Required fields are clearly marked with asterisks
- The final step is a read-only review of all entered data
- Email reminders if onboarding is not completed within N days

**Relevance**: Gusto's approach is closest to the wizard plan. The key difference is that Gusto's onboarding is mandatory and compliance-driven — every step is required. Our system has only one required step (Employee Record), making a full wizard feel like overkill for the optional steps.

### 3.2 Rippling — Hybrid Approach

**Approach**: Mix of checklist (IT setup) + wizard (HR onboarding)

Rippling splits onboarding into two distinct experiences:

1. **IT Setup (Checklist)**: After account creation, the employee sees a checklist:
   - Install Rippling app (with download links per platform)
   - Set up MFA (with QR code)
   - Set up device management
   - Download Slack/Google Workspace apps
   - These tasks are non-sequential and can be done in any order.

2. **HR Onboarding (Wizard)**: After IT setup is complete, the employee enters a wizard:
   - Personal Information
   - Tax Withholding
   - Direct Deposit
   - Benefits Enrollment
   - Emergency Contact
   - Review & Sign

**Why a hybrid**: Rippling recognizes that different types of onboarding tasks benefit from different UX patterns. IT setup tasks are independent actions (install, configure, verify) that work well as a checklist. HR tasks are sequential data entry that benefits from a guided wizard. The IT checklist gates entry to the company dashboard, while the HR wizard is a separate flow.

**Key UX patterns**:
- The IT checklist is a "get started" gate — until the app is installed and MFA is set up, the user can't access the dashboard
- The HR wizard is launched from within the dashboard, not as a gate
- Tasks in the checklist have clear, actionable CTAs ("Download for Mac", "Set Up Now")
- Completed tasks show a green checkmark and become non-interactive (or review-only)

**Relevance**: Rippling's hybrid model is instructive. Our system's library defaults ("Complete Your Profile" and "Explore the Dashboard") are already checklist-like gates. The HR-specific steps (Employee, Profile, Banking, Documents) are the data-entry portion. Rippling suggests that a checklist for the library gates and a wizard for the HR data entry could work — but the two experiences are served separately, not as a single unified flow.

### 3.3 Notion — Workspace Setup Checklist

**Approach**: Checklist with inline actions

Notion's workspace setup for new users is a classic checklist:

```
┌─────────────────────────────────────────┐
│  Welcome to Notion, [Name]              │
│                                         │
│  ☐ Create your first page              │
│    Start with a template or blank page  │
│    [Create Page]                        │
│                                         │
│  ☐ Invite your team                    │
│    Collaborate with teammates           │
│    [Invite People]                      │
│                                         │
│  ☐ Download the app                    │
│    Available on Mac, Windows, iOS       │
│    [Download]                           │
│                                         │
│  ☐ Import from another tool            │
│    Bring in data from Confluence, etc.  │
│    [Import]                             │
└─────────────────────────────────────────┘
```

**Key UX patterns**:
- Each task is a self-contained action with a clear CTA button
- Clicking a CTA either performs the action inline (create page dialog) or navigates (download page)
- Tasks are dismissible — the user can close a task without completing it
- The checklist is shown on the sidebar, not as a full-page gate
- Progress is tracked subtly (a small "3 of 4 complete" indicator)
- The checklist is not a gate — the user can use Notion without completing any tasks

**Why a checklist**: Notion's onboarding tasks are exploratory actions, not data entry. "Create a page" is about learning the product, not filling in required fields. A wizard would be too heavy for these lightweight, self-directed actions. The checklist invites exploration without blocking the user.

**Relevance**: Notion's approach is closest to the checklist plan in spirit. The key difference is that Notion's checklist is not a gate — it's a suggestion. Our onboarding has a hard requirement (Employee Record) that must be completed before the user can access the dashboard, making it more of a gate than Notion's optional checklist.

### 3.4 GitHub — Repository Setup Checklist

**Approach**: Checklist with inline actions on the repository page

GitHub's new repository setup is a checklist embedded in the repository page:

```
☐ Create a new file or upload an existing file
☐ Add a README with information about your project
☐ Add a .gitignore to exclude files
☐ Choose a license for your project
```

**Key UX patterns**:
- Checklist is shown on the main repository page, not as a separate page
- Each item is a link that opens the relevant interface (file editor, license picker)
- Completed items are checked off automatically (detected by GitHub, not manually toggled)
- The checklist is not dismissible — it serves as a persistent guide
- The checklist disappears once all items are completed

**Why a checklist**: GitHub's setup tasks are independent actions on different parts of the system (file creation, README, license). There is no logical sequence. A wizard would be jarring because the user would be bounced between different interfaces. The checklist provides lightweight, non-blocking guidance.

**Relevance**: GitHub's approach is similar to the checklist plan in that tasks are detected automatically (via conditions) rather than manually toggled. The checklist serves as a guide, not a gate. However, GitHub's tasks are navigation actions (click, go somewhere else, do something), while our tasks are data entry actions (click, open a form, fill fields, save).

---

## 4. Comparative Summary Matrix

| Product | Approach | Gate or Guide? | Task Type | Data Entry or Navigation? |
|---------|----------|----------------|-----------|--------------------------|
| **Gusto** | Wizard | Gate (compliance) | Sequential data entry | Data entry |
| **Rippling** | Hybrid (IT checklist + HR wizard) | IT = gate, HR = guide | Mixed | Mixed |
| **Notion** | Checklist | Guide (exploratory) | Independent actions | Navigation + inline actions |
| **GitHub** | Checklist | Guide (persistent) | Independent actions | Navigation |

**Pattern**: Wizards are used when tasks are sequential data entry with compliance/legal requirements. Checklists are used when tasks are independent, exploratory, or navigation-based actions.

---

## 5. Recommendation

### 5.1 Recommendation: Wizard Approach

**The wizard approach is the better choice for this system**, despite the higher implementation cost. Here's why:

#### 5.1.1 The Dependency Problem

The checklist approach has a critical architectural flaw: **the Employee Record is a prerequisite for all other tasks**. `EmployeeProfile` has a foreign key to `Employee`. `EmployeePayrollProfile` has a foreign key to `Employee`. `Document` is polymorphic but typically linked to `Employee`. If a user clicks "Payroll & Banking" before creating an Employee record, the [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php) will fail because `employee_id` cannot be null.

The checklist could mitigate this by:
- **Option A**: Auto-creating the Employee record when the checklist loads. This solves the foreign key problem but eliminates the user's chance to review/confirm their Employee data.
- **Option B**: Disabling dependent tasks until the prerequisite is complete. This turns the checklist into a de facto wizard — tasks are "unlocked" sequentially — but with a worse UX because the user doesn't understand why tasks are disabled.
- **Option C**: Pre-creating a "draft" Employee record and letting the user edit it. This is functionally equivalent to the wizard's Step 1 but with more indirection.

None of these options are clean. The wizard's sequential flow naturally handles this dependency: Step 1 must be completed before Steps 2-5 are accessible.

#### 5.1.2 Form Fit: DataTableForm Is Not Designed for Onboarding

The [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php) is a general-purpose CRUD form designed for data tables. It shows all fields defined in the model config, including system fields (`id`, `created_at`, `company_id`) that are irrelevant or confusing in an onboarding context. It has a "Cancel" button that closes the drawer, but no "Skip" concept. It has no notion of "partial save" (all required fields must be filled). It is designed for operators who know the data model, not for new employees who are seeing it for the first time.

The onboarding experience needs:
- **Curated fields**: Only the fields relevant to a new employee, not all model fields
- **Skip capability**: An explicit "Skip for Now" action that persists
- **Partial saves**: Ability to save partial data on optional tasks
- **Onboarding-specific copy**: Helpful descriptions, tooltips, and guidance
- **Pre-filled data**: Employee ID, company ID, and other context from the invitation

The wizard provides all of these. The checklist would need to either (a) create separate `DataTableForm` configs for onboarding (which duplicates config maintenance), or (b) add onboarding-specific features to `DataTableForm` (which bloats the general-purpose component).

#### 5.1.3 The Library/Consuming-App Boundary

The library provides:
- [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php) — general-purpose CRUD form
- [`WizardForm`](src/Http/Livewire/Wizards/WizardForm.php) — wizard-step form (already exists, used by the library's own wizard system)
- [`Drawer`](src/Http/Livewire/Drawer.php) — global offcanvas drawer
- [`OnboardingCondition`](src/Contracts/OnboardingCondition.php) — condition contract
- [`wizard.blade.php`](src/Resources/views/livewire/wizards/wizard.blade.php) — reusable wizard UI with progress bar, step navigation, and completion screen

The library already has a wizard infrastructure. Building a wizard for onboarding aligns with the library's existing patterns. The checklist approach would be inventing a new pattern that the library doesn't natively support, creating a one-off UX that doesn't benefit from the library's wizard investment.

The wizard plan's use of [`WizardForm`](src/Http/Livewire/Wizards/WizardForm.php) for model-aligned steps (Step 1-3) means those steps can leverage the library's existing wizard form infrastructure, including its `presetData` mechanism for pre-filling fields from the invitation context.

#### 5.1.4 Development Velocity

| Factor | Checklist | Wizard |
|--------|-----------|--------|
| Files to create | 2 | 7 |
| Lines of new code | ~300 | ~1,200 |
| Complexity of new code | Simple orchestration | Complex form UI per step |
| Time to first working prototype | Faster | Slower |

The checklist is faster to build initially. However, the wizard's higher upfront investment pays off in:
- **Better UX** for the end user (guided, polished, celebratory)
- **Better fit** for the data model dependencies
- **Alignment with library patterns** (reuses [`WizardForm`](src/Http/Livewire/Wizards/WizardForm.php) and wizard UI)
- **Easier to extend** with onboarding-specific features (progress emails, admin preview, A/B testing)

#### 5.1.5 Long-Term Maintainability

The checklist's single biggest advantage — reusing [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php) — is also its single biggest risk. If `DataTableForm` changes (a new required parameter, a behavioral change, a breaking refactor), the checklist onboarding experience could break silently. The checklist is coupled to `DataTableForm`'s internal behavior.

The wizard's custom form UI is decoupled from `DataTableForm`. Changes to `DataTableForm` don't affect the wizard. The wizard's forms are purpose-built for onboarding and can evolve independently.

However, the wizard has more code to maintain (7 files vs 2). Every new field added to a model needs to be added to the wizard partial. This is a real maintenance cost, but the onboarding experience is stable — it changes infrequently compared to the general CRUD forms.

### 5.2 When the Checklist Would Be Better

The checklist approach would be the clear winner if:

1. **All tasks were truly independent** — no foreign key dependencies between models. If the Employee Record didn't need to exist before the Employee Profile could be created, the checklist's non-linear flexibility would be a strength.

2. **The DataTableForm was purpose-built for onboarding** — if it supported skip, partial save, curated field sets, and onboarding-specific copy, the checklist could delegate entirely to the library's infrastructure.

3. **The onboarding was a guide, not a gate** — like Notion's checklist, where tasks are suggestions rather than requirements. If the user could access the dashboard without completing any onboarding tasks, a checklist would be more appropriate.

4. **There were many more tasks** (10+) — the checklist scales better than a wizard for large numbers of tasks. A 10-step wizard is tedious; a 10-item checklist is manageable.

### 5.3 Final Verdict

| Criterion | Winner |
|-----------|--------|
| Implementation effort | Checklist |
| Code reuse | Checklist |
| User guidance | Wizard |
| Flexibility (non-linear) | Checklist |
| Mobile responsiveness | Checklist |
| Revisit-ability | Checklist |
| Dependency management | **Wizard** |
| Adding/removing tasks | Checklist |
| First-time user experience | **Wizard** |
| Maintenance burden | Checklist |
| **Business fit (required gating)** | **Wizard** |
| **Library pattern alignment** | **Wizard** |

The checklist wins on 7 of 10 technical dimensions. But the wizard wins on the dimensions that matter most for this specific use case: **dependency management**, **first-time user experience**, **business fit**, and **library pattern alignment**.

The Employee Record dependency is the decisive factor. The checklist cannot gracefully handle the prerequisite relationship without becoming a de facto wizard (with disabled tasks and sequential unlocking). If we're going to build a sequential experience anyway, we should build a proper wizard with the guidance, progress tracking, and polish that users expect from an onboarding flow.

**Recommendation: Build the wizard.** Use the library's existing [`wizard.blade.php`](src/Resources/views/livewire/wizards/wizard.blade.php) and [`WizardForm`](src/Http/Livewire/Wizards/WizardForm.php) infrastructure. Invest in the UX quality that onboarding deserves. The checklist's code reuse advantage is real but ultimately a false economy — it saves development time at the cost of user experience in the most critical moment of the user's journey.

---

## 6. Appendix: Hybrid Option (For Consideration)

A third option exists: a **hybrid approach** modeled after Rippling. The library's default steps ("Complete Your Profile", "Explore the Dashboard") use the checklist pattern, and the HR-specific steps use the wizard pattern. The two experiences are served separately:

1. **Library gates** (checklist): After invitation acceptance, the user sees a simple checklist: "Complete Your Profile" and "Explore the Dashboard." These are independent actions with clear CTAs. This lives in the library.

2. **HR onboarding** (wizard): After the library gates are cleared, the user enters the consolidated wizard at `/onboarding` for the 5 HR-specific steps. This lives in the HR module.

This approach:
- **Aligns with Rippling's proven pattern** (IT checklist → HR wizard)
- **Keeps the library's existing onboarding infrastructure** unchanged
- **Gives each experience the right UX** (checklist for independent gates, wizard for sequential data entry)
- **Creates a natural handoff point** between library and module concerns

This hybrid is not recommended as the primary approach because it adds complexity (two onboarding experiences instead of one), but it is worth noting as a possible future direction if the library's onboarding steps grow beyond the current two defaults.