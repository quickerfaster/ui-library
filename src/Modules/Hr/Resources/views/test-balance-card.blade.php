<x-layout>
    <x-slot name="topNav">
        <livewire:qf.top-nav moduleName="hr">
    </x-slot>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h3>Balance Card Widget Test</h3>
                <p class="text-muted">Testing the new BalanceCardWidget component</p>
            </div>
        </div>

        <div class="row g-4">
            {{-- Test with static data --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.balance-card-widget
                    widgetId="test_vacation"
                    :config="[
                        'type' => 'balance-card',
                        'title' => 'Vacation',
                        'current' => 4,
                        'total' => 8,
                        'unit' => 'days',
                        'icon' => 'fas fa-umbrella-beach',
                        'color' => 'info',
                        'action' => [
                            'label' => 'View Details',
                            'url' => '#',
                            'icon' => 'fas fa-arrow-right'
                        ]
                    ]"
                    :key="'test-vacation'"
                />
            </div>

            {{-- Test with different colors --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.balance-card-widget
                    widgetId="test_sick"
                    :config="[
                        'type' => 'balance-card',
                        'title' => 'Sick Leave',
                        'current' => 10,
                        'total' => 10,
                        'unit' => 'days',
                        'icon' => 'fas fa-heartbeat',
                        'color' => 'success'
                    ]"
                    :key="'test-sick'"
                />
            </div>

            {{-- Test with low balance --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.balance-card-widget
                    widgetId="test_low"
                    :config="[
                        'type' => 'balance-card',
                        'title' => 'Personal Days',
                        'current' => 0.5,
                        'total' => 3,
                        'unit' => 'days',
                        'icon' => 'fas fa-user-clock',
                        'color' => 'warning'
                    ]"
                    :key="'test-low'"
                />
            </div>

            {{-- Test with event action --}}
            <div class="col-12 col-sm-6 col-lg-3">
                <livewire:qf::widgets.cards.balance-card-widget
                    widgetId="test_event"
                    :config="[
                        'type' => 'balance-card',
                        'title' => 'Request Leave',
                        'current' => 0,
                        'total' => 1,
                        'unit' => 'action',
                        'icon' => 'fas fa-plus-circle',
                        'color' => 'primary',
                        'action' => [
                            'label' => 'Click to Request',
                            'event' => 'testOpenModal',
                            'icon' => 'fas fa-rocket'
                        ]
                    ]"
                    :key="'test-event'"
                />
            </div>
        </div>

        {{-- Test loading state --}}
        <div class="row mt-4">
            <div class="col-12">
                <h4>Loading State Test</h4>
                <div class="row g-4">
                    <div class="col-12 col-sm-6 col-lg-3">
                        @php
                            // Simulate loading by not passing initialData
                        @endphp
                        <livewire:qf::widgets.cards.balance-card-widget
                            widgetId="test_loading"
                            :config="[
                                'type' => 'balance-card',
                                'title' => 'Loading...',
                                'icon' => 'fas fa-spinner',
                                'color' => 'secondary'
                            ]"
                            :key="'test-loading'"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript for testing events --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Livewire.on('testOpenModal', () => {
                alert('Modal would open here!');
            });

            // Test refresh functionality
            setTimeout(() => {
                console.log('Testing widget refresh...');
                Livewire.dispatch('refreshWidget', 'test_vacation');
            }, 3000);
        });
    </script>



<h3>Guide</h3>
<textarea style="width: 100%; height: 500px">
    7. Testing Steps:
Copy the PHP file to app/Http/Livewire/Widgets/Cards/BalanceCardWidget.php

Copy the Blade file to your views directory

Create the test view and visit /test/balance-card-test

Check for:

Cards display correctly with gradients

Progress bars show correct percentages

Action links/buttons work

Loading spinner appears

Hover effects work

Colors are correct

Expected Output:
You should see 4-5 cards showing:

Vacation: 4/8 days (50% progress bar, info color)

Sick Leave: 10/10 days (100% progress, success color)

Personal Days: 0.5/3 days (~17% progress, warning color)

Request Leave card with click event
</textarea>
</x-layouts>
