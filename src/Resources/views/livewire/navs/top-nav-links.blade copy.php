{{-- Top Bar Links for hr --}}

@if(auth()->user()?->can('view_employee_profile'))
    <system::layouts.navbars.top-bar-link-item
        iconClasses="fas fa-id-card-alt top-bar-icon"
        url="hr/employee-profiles"
        title="Employee Profiles"
    />
@endif