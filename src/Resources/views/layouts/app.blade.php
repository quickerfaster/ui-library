<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name', 'Laravel'))</title>
    <!-- Soft UI [bootstrap] Theme CSS Files -->
    <link id="pagestyle" href="{{ asset('bootstrap/assets/css/soft-ui-dashboard.css?v=1.0.3') }}" rel="stylesheet" />
    {{-- - --<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* Fixed sidebar */
        .fixed-sidebar {
            position: fixed;
            top: 60px;
            /* Height of your top bar – adjust as needed */
            left: 0;
            bottom: 0;
            width: 250px;
            overflow-y: auto;
            z-index: 1000;
        }

        /* Main content area – pushed to the right */
        .main-content {
            margin-left: 250px;
            /* Same as sidebar width */
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* If the sidebar is collapsed (optional), you can toggle a class */
        .main-content.sidebar-collapsed {
            margin-left: 0;
        }
        }
    </style>

<body>
    @props(['moduleName' => 'hr', 'pageComponent' => null, 'pageParams' => []])

    <br />
    <br />

    {{-- - --<x-qf.navigation-layout :moduleName="$moduleName" :pageComponent="$pageComponent" :pageParams="$pageParams" />--}}
    {{ $slot }}
    <livewire:qf.alert-modal configKey="hr_attendance" />
    <livewire:qf.detail-modal configKey="hr_attendance" />
    <livewire:qf.form-modal configKey="hr_attendance" />
    <livewire:qf.import-modal configKey="hr_attendance" />
    <livewire:qf.export-modal configKey="hr_attendance" />

    {{-- <x-qf::onboarding.app-onboarding-tasks />
    <x-qf::onboarding.app-onboarding-tour /> --}}

    {{-- <button onclick="Livewire.dispatch('openAddModal', '{{ $configKey }}')"> Add Record Modal </button>
    <button onclick="Livewire.dispatch('openEditModal', ['{{ $configKey }}', 1])"> Edit Record Modal </button> --}}

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script src="{{ asset('assets/js/quicker-faster.js') }}"></script>


    @livewireScripts
    @stack('scripts')









</body>

</html>
