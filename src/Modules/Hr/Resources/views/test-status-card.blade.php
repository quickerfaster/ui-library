<x-layout>
    <x-slot name="topNav">
        <livewire:qf.top-nav moduleName="hr">
    </x-slot>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h3>Status Card Widget Test</h3>
                <p class="text-muted">Testing the new StatusCardWidget component</p>
            </div>
        </div>

        <div class="row g-4">
            {{-- Test 1: Pending Approvals (Warning) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.status-card-widget
                    widgetId="pending_approvals"
                    :config="[
                        'type' => 'status-card',
                        'title' => 'Pending Approvals',
                        'count' => 3,
                        'status_text' => 'Awaiting review',
                        'icon' => 'fas fa-inbox',
                        'color' => 'warning',
                        'badge_text' => 'Needs attention',
                        'action' => [
                            'type' => 'button',
                            'label' => 'Review Now',
                            'url' => '/hr/leave/pending',
                            'icon' => 'fas fa-eye'
                        ]
                    ]"
                    :key="'pending-approvals'"
                />
            </div>

            {{-- Test 2: Upcoming Time Off (Primary) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.status-card-widget
                    widgetId="upcoming_timeoff"
                    :config="[
                        'type' => 'status-card',
                        'title' => 'Upcoming Time Off',
                        'count' => 1,
                        'unit' => 'leave requests',
                        'icon' => 'fas fa-plane',
                        'color' => 'primary',
                        'action' => [
                            'label' => 'View Calendar',
                            'event' => 'openCalendar',
                            'icon' => 'fas fa-calendar'
                        ]
                    ]"
                    :key="'upcoming-timeoff'"
                />
            </div>

            {{-- Test 3: Team Out Today (Success) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.status-card-widget
                    widgetId="team_out_today"
                    :config="[
                        'type' => 'status-card',
                        'title' => 'Team Out Today',
                        'count' => 2,
                        'status' => 'team members absent',
                        'icon' => 'fas fa-users',
                        'color' => 'info',
                        'show_badge' => true,
                        'action' => [
                            'type' => 'button',
                            'label' => 'View Team',
                            'url' => '#',
                            'icon' => 'fas fa-user-friends'
                        ]
                    ]"
                    :key="'team-out-today'"
                />
            </div>

            {{-- Test 4: Overdue Requests (Danger) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.status-card-widget
                    widgetId="overdue_requests"
                    :config="[
                        'type' => 'status-card',
                        'title' => 'Overdue Requests',
                        'count' => 5,
                        'icon' => 'fas fa-clock',
                        'color' => 'danger',
                        'badge_text' => 'Urgent',
                        'action' => [
                            'type' => 'button',
                            'label' => 'Process Now',
                            'event' => 'processOverdue',
                            'icon' => 'fas fa-bolt'
                        ]
                    ]"
                    :key="'overdue-requests'"
                />
            </div>

            {{-- Test 5: Zero Count (Success - All clear) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.status-card-widget
                    widgetId="zero_count"
                    :config="[
                        'type' => 'status-card',
                        'title' => 'Completed Tasks',
                        'count' => 0,
                        'status_text' => 'All tasks completed',
                        'icon' => 'fas fa-check-circle',
                        'color' => 'success',
                        'show_badge' => false,
                        'action' => [
                            'label' => 'View All',
                            'url' => '#',
                            'icon' => 'fas fa-list'
                        ]
                    ]"
                    :key="'zero-count'"
                />
            </div>

            {{-- Test 6: High Count with Auto-badge --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.status-card-widget
                    widgetId="high_count"
                    :config="[
                        'type' => 'status-card',
                        'title' => 'New Messages',
                        'count' => 42,
                        'unit' => 'messages',
                        'icon' => 'fas fa-envelope',
                        'color' => 'primary',
                        'action' => [
                            'type' => 'button',
                            'label' => 'Open Inbox',
                            'url' => '#',
                            'icon' => 'fas fa-inbox'
                        ]
                    ]"
                    :key="'high-count'"
                />
            </div>

            {{-- Test 7: Custom Status Text --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.status-card-widget
                    widgetId="custom_status"
                    :config="[
                        'type' => 'status-card',
                        'title' => 'Active Projects',
                        'count' => 7,
                        'status_text' => 'In progress this month',
                        'icon' => 'fas fa-tasks',
                        'color' => 'info',
                        'badge_text' => 'Active',
                        'action' => [
                            'label' => 'Manage',
                            'url' => '#',
                            'icon' => 'fas fa-cog'
                        ]
                    ]"
                    :key="'custom-status'"
                />
            </div>

            {{-- Test 8: Simple link action --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.status-card-widget
                    widgetId="simple_action"
                    :config="[
                        'type' => 'status-card',
                        'title' => 'Open Tickets',
                        'count' => 12,
                        'icon' => 'fas fa-ticket-alt',
                        'color' => 'warning',
                        'action' => [
                            'label' => 'View Tickets',
                            'url' => '/tickets'
                        ]
                    ]"
                    :key="'simple-action'"
                />
            </div>
        </div>

        {{-- Test loading state --}}
        <div class="row mt-4">
            <div class="col-12">
                <h4>Loading State Test</h4>
                <div class="row g-4">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <livewire:qf::widgets.cards.status-card-widget
                            widgetId="loading_test"
                            :config="[
                                'type' => 'status-card',
                                'title' => 'Loading Data',
                                'icon' => 'fas fa-spinner',
                                'color' => 'secondary'
                            ]"
                            :key="'loading-test'"
                        />
                    </div>
                </div>
            </div>
        </div>

        {{-- Test data update --}}
        <div class="row mt-4">
            <div class="col-12">
                <h4>Data Update Test</h4>
                <div class="row g-4">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <livewire:qf::widgets.cards.status-card-widget
                            widgetId="dynamic_test"
                            :config="[
                                'type' => 'status-card',
                                'title' => 'Dynamic Count',
                                'count' => 1,
                                'icon' => 'fas fa-sync',
                                'color' => 'primary'
                            ]"
                            :initialData="['count' => 1]"
                            :key="'dynamic-test'"
                        />
                    </div>
                </div>
                <button class="btn btn-sm btn-primary mt-2" onclick="updateTestData()">
                    Update Count to 5
                </button>
            </div>
        </div>
    </div>

    {{-- JavaScript for testing --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Test event handling
            Livewire.on('openCalendar', (widgetId) => {
                alert(`Calendar would open for widget: ${widgetId}`);
            });

            Livewire.on('processOverdue', (widgetId) => {
                alert(`Processing overdue items for widget: ${widgetId}`);
                // Simulate data change
                Livewire.dispatch('dashboardDataUpdated', {
                    'overdue_requests': { count: 0 }
                });
            });

            Livewire.on('navigateTo', (url) => {
                alert(`Would navigate to: ${url}`);
                // window.location.href = url;
            });
        });

        function updateTestData() {
            Livewire.dispatch('dashboardDataUpdated', {
                'dynamic_test': { count: 5 }
            });
        }
    </script>


<h3>Guide</h3>
<textarea style="width: 100%; height: 500px">
    5. Testing Steps:
Copy the PHP file to app/Http/Livewire/Widgets/Cards/StatusCardWidget.php

Copy the Blade file to your views directory

Create the test view and visit /test/status-card-test

Test each card:

Card 1: Should show "3" with warning color, "Needs attention" badge, button with count badge

Card 2: Should show "1" with primary color, event-based action

Card 3: Should show "2" with info color, button action

Card 4: Should show "5" with danger color, "Urgent" badge, event action

Card 5: Should show "0" with success color, disabled button

Card 6: Should show "42" with auto-generated badge

Card 7: Should show custom status text

Card 8: Should show simple link action

6. Expected Features to Test:
✅ Count Display: Large prominent number
✅ Status Text: Clear description below count
✅ Color Coding: Correct gradient for each color
✅ Badges: Auto-generated or custom badge text
✅ Action Buttons:

URL-based navigation

Event-based actions

Disabled state when count = 0

Button with count badge
✅ Responsive Design: Works on mobile/tablet
✅ Hover Effects: Card lifts on hover
✅ Loading State: Spinner when loading
✅ Data Updates: Live updates when data changes
</textarea>
</x-layouts>
