{{--
    Phase 4.4: Dropdown Application/Organization Switcher
    Placed at the top of the sidebar, above navigation sections.
    Displays the current organization name with a dropdown to switch.

    Available variables:
    - $currentOrganization: ?array{id, name, logo} — the active organization
    - $userOrganizations: \Illuminate\Support\Collection — all organizations the user belongs to
    - $switchRoute: string — named route for switching (default: 'company.switch')
--}}
@php
    $switchRoute = $switchRoute ?? 'company.switch';
    $hasMultiple = $userOrganizations instanceof \Illuminate\Support\Collection && $userOrganizations->count() > 1;
    $hasSingle = $userOrganizations instanceof \Illuminate\Support\Collection && $userOrganizations->count() === 1;
    $hasNone = !($userOrganizations instanceof \Illuminate\Support\Collection) || $userOrganizations->isEmpty();

    // If no current org is set but user has orgs, use the first one
    if (!$currentOrganization && $userOrganizations instanceof \Illuminate\Support\Collection && $userOrganizations->isNotEmpty()) {
        $currentOrganization = $userOrganizations->first();
    }

    $orgName = $currentOrganization['name'] ?? null;
    $orgLogo = $currentOrganization['logo'] ?? null;
    $orgId = $currentOrganization['id'] ?? null;
@endphp

@if (!$hasNone)
<div
    class="application-switcher border-bottom"
    x-data="{
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; }
    }"
    @click.away="close()"
    @keydown.escape.window="close()">

    {{-- Trigger Button --}}
    <button
        type="button"
        class="app-switcher-trigger"
        @click="toggle()"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
        aria-label="{{ __('Switch organization') }}"
        title="{{ $orgName }}">

        {{-- Organization Icon/Logo --}}
        <span class="app-switcher-icon" aria-hidden="true">
            @if ($orgLogo)
                <img src="{{ $orgLogo }}" alt="{{ $orgName }} logo" class="org-logo-img">
            @else
                <i class="fas fa-building text-muted"></i>
            @endif
        </span>

        {{-- Organization Name (hidden in icon-only sidebar mode) --}}
        <span class="app-switcher-label">{{ $orgName }}</span>

        {{-- Chevron (only if multiple orgs) --}}
        @if ($hasMultiple)
            <i class="fas fa-chevron-down app-switcher-chevron"
               :class="{ 'rotated': open }"
               aria-hidden="true"></i>
        @endif
    </button>

    {{-- Dropdown Menu (only if multiple orgs) --}}
    @if ($hasMultiple)
        <ul
            class="app-switcher-dropdown"
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak
            role="listbox"
            aria-label="{{ __('Select organization') }}"
            @click.outside="close()">

            @foreach ($userOrganizations as $org)
                @php
                    $isCurrent = ($org['id'] ?? null) == $orgId;
                @endphp
                <li role="option"
                    :aria-selected="{{ $isCurrent ? 'true' : 'false' }}"
                    class="app-switcher-option {{ $isCurrent ? 'current' : '' }}">
                    <form method="POST" action="{{ route($switchRoute, ['company' => $org['id']]) }}" class="d-block">
                        @csrf
                        <button type="submit" class="app-switcher-option-btn">
                            <span class="app-switcher-option-icon" aria-hidden="true">
                                @if (!empty($org['logo']))
                                    <img src="{{ $org['logo'] }}" alt="" class="org-logo-img-sm">
                                @else
                                    <i class="fas fa-building text-muted"></i>
                                @endif
                            </span>
                            <span class="app-switcher-option-label">{{ $org['name'] }}</span>
                            @if ($isCurrent)
                                <i class="fas fa-check text-primary ms-auto" aria-hidden="true"></i>
                            @endif
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endif

{{-- Inline styles scoped to the application switcher --}}
<style>
    .application-switcher {
        position: relative;
        padding: 0.5rem 0.75rem;
    }

    .app-switcher-trigger {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid transparent;
        border-radius: 0.5rem;
        background: transparent;
        color: inherit;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease;
        font-size: 0.875rem;
        line-height: 1.4;
        text-align: left;
    }

    .app-switcher-trigger:hover {
        background-color: rgba(0, 0, 0, 0.04);
        border-color: rgba(0, 0, 0, 0.08);
    }

    .app-switcher-trigger:focus-visible {
        outline: 2px solid #0d6efd;
        outline-offset: -2px;
        border-radius: 0.5rem;
    }

    .app-switcher-icon {
        flex-shrink: 0;
        width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.625rem;
        font-size: 0.9rem;
    }

    .org-logo-img {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 0.25rem;
        object-fit: cover;
    }

    .org-logo-img-sm {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 0.2rem;
        object-fit: cover;
    }

    .app-switcher-label {
        flex: 1;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    .app-switcher-chevron {
        flex-shrink: 0;
        margin-left: 0.5rem;
        font-size: 0.65rem;
        transition: transform 0.2s ease;
        color: #6c757d;
    }

    .app-switcher-chevron.rotated {
        transform: rotate(180deg);
    }

    /* Dropdown */
    .app-switcher-dropdown {
        position: absolute;
        left: 0.75rem;
        right: 0.75rem;
        top: calc(100% - 0.25rem);
        z-index: 1050;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12);
        list-style: none;
        margin: 0;
        padding: 0.375rem 0;
        max-height: 16rem;
        overflow-y: auto;
    }

    .app-switcher-option {
        margin: 0;
        padding: 0;
    }

    .app-switcher-option-btn {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0.5rem 1rem;
        border: none;
        background: transparent;
        color: inherit;
        cursor: pointer;
        font-size: 0.8125rem;
        text-align: left;
        transition: background-color 0.1s ease;
    }

    .app-switcher-option-btn:hover {
        background-color: rgba(13, 110, 253, 0.06);
    }

    .app-switcher-option.current .app-switcher-option-btn {
        font-weight: 600;
        background-color: rgba(13, 110, 253, 0.04);
    }

    .app-switcher-option-icon {
        flex-shrink: 0;
        width: 1.25rem;
        height: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
        font-size: 0.75rem;
    }

    .app-switcher-option-label {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    /* Icon-only sidebar mode: hide label and chevron */
    .sidebar-icon .app-switcher-label,
    .sidebar-icon .app-switcher-chevron {
        display: none !important;
    }

    .sidebar-icon .app-switcher-trigger {
        justify-content: center;
        padding: 0.5rem;
    }

    .sidebar-icon .app-switcher-icon {
        margin-right: 0;
    }

    .sidebar-icon .app-switcher-dropdown {
        left: 0.25rem;
        right: auto;
        min-width: 12rem;
    }

    /* x-cloak support */
    [x-cloak] { display: none !important; }
</style>