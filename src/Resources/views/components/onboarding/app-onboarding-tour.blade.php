{{-- Driver.js Assets --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css" />
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>

@auth
    @php
        $setupCompleted = \QuickerFaster\UILibrary\Models\SystemSetting::first()->setup_completed ?? false;
    @endphp
    {{-- NOTE THAT SETUP TASK MUST BE COMPLETED BEFORE THIS COULD BE INVOLKED --}}
    {{--  UNCOMMENT THE FOLLOWING LINE INSIDE THE SERVICE PROVIDER TO UNLOCK THE SETUP MIDDLEWARE
        $kernel->appendMiddlewareToGroup('web', CheckSetup::class); --}}
    {{-- - -@if ($setupCompleted && !auth()->user()->has_seen_tour) --}}
    @if (!auth()->user()->has_seen_tour)
        <script>
            document.addEventListener('livewire:initialized', function() {
                // Small delay to ensure DOM and Livewire components are fully rendered
                setTimeout(() => {
                    const steps = @json(config('app_tour.workspace', []));

                    if (!steps.length) return;

                    // 1. Mobile Check: Disable tour on smaller screens (Standard Breakpoint)
                    if (window.innerWidth <= 768) {
                        console.log('Tour disabled for mobile screens.');
                        return;
                    }

                    const driver = window.driver.js.driver;

                    const driverObj = driver({
                        showProgress: true,
                        allowClose: true, // Shows the 'X' button
                        steps: steps,

                        // 2. The Persistence Logic: Save status when tour is closed or finished
                        onDestroyStarted: () => {
                            // Always destroy the overlay immediately for a snappy UI
                            driverObj.destroy();

                            // Send the completion signal to your Laravel route
                            // This ensures the user won't see the tour again next time they log in
                            fetch('{{ route('tour.complete') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    completed_at: new Date().toISOString()
                                })
                            }).catch(err => console.error('Tour status sync failed:', err));
                        }
                    });

                    // 3. Launch the tour
                    driverObj.drive();

                }, 800); // Slightly increased delay for smoother asset loading
            });
        </script>
    @endif
@endauth
