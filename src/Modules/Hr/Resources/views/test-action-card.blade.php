<x-layout>
    <x-slot name="topNav">
        <livewire:qf.top-nav moduleName="hr">
    </x-slot>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h3>Action Card Widget Test</h3>
                <p class="text-muted">Testing the new ActionCardWidget component</p>
            </div>
        </div>

        <div class="row g-4">
            {{-- Test 1: Basic Action (Primary) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_basic"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'Quick Action',
                        'description' => 'Request new leave with one click',
                        'color' => 'primary',
                        'button_size' => 'default',
                        'button_variant' => 'white',
                        'action' => [
                            'label' => 'Request Leave',
                            'icon' => 'fas fa-calendar-plus',
                            'event' => 'openLeaveRequestModal',
                            'success_message' => 'Leave request form opened!'
                        ]
                    ]"
                    :key="'test-basic'"
                />
            </div>

            {{-- Test 2: URL Navigation (Success) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_url"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'View Team Calendar',
                        'description' => 'See who\'s out this week',
                        'color' => 'success',
                        'action' => [
                            'label' => 'Open Calendar',
                            'icon' => 'fas fa-calendar-alt',
                            'url' => '/hr/leave/calendar',
                            'helper' => 'Opens in current window'
                        ]
                    ]"
                    :key="'test-url'"
                />
            </div>

            {{-- Test 3: New Tab (Info) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_newtab"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'Export Report',
                        'description' => 'Download detailed leave report',
                        'color' => 'info',
                        'action' => [
                            'label' => 'Download PDF',
                            'icon' => 'fas fa-file-pdf',
                            'url' => '/hr/leave/report/pdf',
                            'target' => '_blank',
                            'helper' => 'Opens in new tab'
                        ]
                    ]"
                    :key="'test-newtab'"
                />
            </div>

            {{-- Test 4: With Count Badge (Warning) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_count"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'Pending Approvals',
                        'description' => 'Review requests waiting for you',
                        'color' => 'warning',
                        'action' => [
                            'label' => 'Review',
                            'icon' => 'fas fa-inbox',
                            'count' => 5,
                            'event' => 'showPendingApprovals',
                            'tooltip' => '5 items need your attention'
                        ]
                    ]"
                    :key="'test-count'"
                />
            </div>

            {{-- Test 5: With Confirmation (Danger) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_confirm"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'Clear All',
                        'description' => 'Remove all pending requests',
                        'color' => 'danger',
                        'action' => [
                            'label' => 'Clear All',
                            'icon' => 'fas fa-trash',
                            'event' => 'clearAllRequests',
                            'confirm' => true,
                            'confirm_title' => 'Clear All Requests',
                            'confirm_message' => 'Are you sure you want to clear all pending requests? This cannot be undone.',
                            'success_message' => 'All requests cleared successfully'
                        ]
                    ]"
                    :key="'test-confirm'"
                />
            </div>

            {{-- Test 6: Disabled Action (Secondary) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_disabled"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'Submit Timesheet',
                        'description' => 'Submit your weekly timesheet',
                        'color' => 'secondary',
                        'action' => [
                            'label' => 'Submit Now',
                            'icon' => 'fas fa-paper-plane',
                            'disabled' => true,
                            'helper' => 'Available every Friday'
                        ]
                    ]"
                    :key="'test-disabled'"
                />
            </div>

            {{-- Test 7: Small Button (Light) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_small"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'Quick Update',
                        'description' => 'Mark as completed',
                        'color' => 'light',
                        'button_size' => 'sm',
                        'button_variant' => 'outline',
                        'action' => [
                            'label' => 'Complete',
                            'icon' => 'fas fa-check',
                            'event' => 'markComplete',
                            'processing_label' => 'Completing...'
                        ]
                    ]"
                    :key="'test-small'"
                />
            </div>

            {{-- Test 8: Large Button (Dark) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_large"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'Important Action',
                        'description' => 'Critical system action',
                        'color' => 'dark',
                        'button_size' => 'lg',
                        'action' => [
                            'label' => 'Execute',
                            'icon' => 'fas fa-bolt',
                            'event' => 'executeCriticalAction',
                            'error_message' => 'Action failed. Please try again.'
                        ]
                    ]"
                    :key="'test-large'"
                />
            </div>

            {{-- Test 9: Custom Method (Primary) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_method"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'Approve All',
                        'description' => 'Approve all pending items',
                        'color' => 'primary',
                        'action' => [
                            'label' => 'Approve All',
                            'icon' => 'fas fa-check-double',
                            'method' => 'quickApproveAll'
                        ]
                    ]"
                    :key="'test-method'"
                />
            </div>

            {{-- Test 10: Multiple Actions (Info) --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.action-card-widget
                    widgetId="test_multi"
                    :config="[
                        'type' => 'action-card',
                        'title' => 'Team Actions',
                        'description' => 'Manage team settings',
                        'color' => 'info',
                        'action' => [
                            'label' => 'Manage Team',
                            'icon' => 'fas fa-users-cog',
                            'event' => 'openTeamManager',
                            'helper' => 'Opens team management panel'
                        ]
                    ]"
                    :key="'test-multi'"
                />
            </div>
        </div>

        {{-- Test loading state --}}
        <div class="row mt-4">
            <div class="col-12">
                <h4>Loading State Test</h4>
                <div class="row g-4">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <livewire:qf::widgets.cards.action-card-widget
                            widgetId="test_loading"
                            :config="[
                                'type' => 'action-card',
                                'title' => 'Loading...',
                                'description' => 'Fetching action data',
                                'color' => 'secondary'
                            ]"
                            :key="'test-loading'"
                        />
                    </div>
                </div>
                <button class="btn btn-sm btn-primary mt-2" onclick="refreshTest()">
                    Trigger Refresh
                </button>
            </div>
        </div>

        {{-- Event Test Area --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Event Test Log</h5>
                        <div id="eventLog" class="border rounded p-3 bg-light" style="min-height: 100px; max-height: 200px; overflow-y: auto;">
                            <small class="text-muted">Event logs will appear here...</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript for testing --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const eventLog = document.getElementById('eventLog');

            function logEvent(message) {
                const timestamp = new Date().toLocaleTimeString();
                const logEntry = document.createElement('div');
                logEntry.className = 'mb-1';
                logEntry.innerHTML = `<small><strong>${timestamp}:</strong> ${message}</small>`;
                eventLog.prepend(logEntry);

                // Keep only last 10 entries
                const entries = eventLog.querySelectorAll('div');
                if (entries.length > 10) {
                    entries[entries.length - 1].remove();
                }
            }

            // Test event handling
            Livewire.on('openLeaveRequestModal', (widgetId) => {
                logEvent(`Widget ${widgetId}: Leave request modal opened`);
                alert('Leave request modal would open here!');
            });

            Livewire.on('showPendingApprovals', (widgetId) => {
                logEvent(`Widget ${widgetId}: Showing pending approvals`);
                // Simulate API call
                setTimeout(() => {
                    Livewire.find(widgetId).call('executeAction');
                }, 1000);
            });

            Livewire.on('clearAllRequests', (widgetId) => {
                logEvent(`Widget ${widgetId}: Clearing all requests`);
                // Simulate clearing
                setTimeout(() => {
                    logEvent(`Widget ${widgetId}: All requests cleared`);
                }, 1500);
            });

            Livewire.on('markComplete', (widgetId) => {
                logEvent(`Widget ${widgetId}: Marking as complete`);
            });

            Livewire.on('executeCriticalAction', (widgetId) => {
                logEvent(`Widget ${widgetId}: Executing critical action`);
                // Simulate random failure
                if (Math.random() > 0.5) {
                    throw new Error('Random failure for testing');
                }
            });

            Livewire.on('openTeamManager', (widgetId) => {
                logEvent(`Widget ${widgetId}: Opening team manager`);
            });

            // Listen for all Livewire events for debugging
            Livewire.hook('message.processed', (message, component) => {
                if (message.updateQueue && message.updateQueue.length > 0) {
                    message.updateQueue.forEach(update => {
                        if (update.type === 'fireEvent' && update.payload.event) {
                            logEvent(`Livewire Event: ${update.payload.event}`);
                        }
                    });
                }
            });
        });

        function refreshTest() {
            Livewire.dispatch('refreshWidget', 'test_loading');
            setTimeout(() => {
                alert('Refresh completed!');
            }, 1000);
        }
    </script>




<h3>Guide</h3>
<textarea style="width: 100%; height: 500px">

5. Testing Steps:
Copy the PHP file to app/Http/Livewire/Widgets/Cards/ActionCardWidget.php

Copy the Blade file to your views directory

Create the test view and visit /test/action-card-test

Test each card:

Card 1: Basic event action with success message

Card 2: URL navigation (same window)

Card 3: URL navigation (new tab)

Card 4: Action with count badge

Card 5: Action with confirmation dialog

Card 6: Disabled action

Card 7: Small outline button

Card 8: Large button with error handling

Card 9: Custom method execution

Card 10: Basic team action

6. Expected Features to Test:
✅ Button Variants: Filled, outline, white buttons
✅ Button Sizes: Small, default, large
✅ Action Types:

Event emission

URL navigation (same window)

URL navigation (new tab)

Custom method execution
✅ Processing States: Loading spinner, processing label
✅ Confirmation Dialogs: Confirm before action
✅ Result Feedback: Success/error messages with auto-clear
✅ Disabled State: Proper styling and behavior
✅ Count Badges: Display count on button
✅ Helper Text: Additional information below button
✅ Tooltips: Hover tooltips
✅ Responsive Design: Works on all screen sizes

7. Advanced Features:
Custom Methods: You can add any method to the component and call it via config

Event Chaining: One action can trigger multiple events

Result Handling: Success/error messages with different styles

Auto-refresh: Can trigger dashboard refresh

Browser Events: Open new tabs, show confirmation dialogs





</textarea>


</x-layouts>
