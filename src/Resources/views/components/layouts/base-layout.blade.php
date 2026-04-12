<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    {{-- Your CSS assets (from config) --}}
    <link id="pagestyle" href="{{ config('ui-library.theme.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />




<link id="pagestyle" href="{{ asset('bootstrap/assets/css/soft-ui-dashboard.css?v=1.0.3') }}" rel="stylesheet" />
    {{-- - --<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />





    {{-- Optional stack for additional styles --}}
    {{ $styles ?? '' }}
</head>
<body>
    {{-- Main content will be injected here --}}


    {{ $slot }} 

    {{-- Global Livewire modals --}}
    <livewire:qf.alert-modal />
    <livewire:qf.detail-modal />
    <livewire:qf.form-modal />
    <livewire:qf.import-modal />
    <livewire:qf.export-modal />

    {{-- Scripts --}}

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('assets/js/quicker-faster.js') }}"></script>
    



    {{-- <x-qf::onboarding.app-onboarding-tasks /> --}}
    <x-qf::onboarding.app-onboarding-tour /> 



<!-- Global Loading Spinner with Alpine.js -->





    @livewireScripts

    {{-- Optional stack for additional scripts --}}
    {{ $scripts ?? '' }}
    @stack('scripts')
</body>
</html>