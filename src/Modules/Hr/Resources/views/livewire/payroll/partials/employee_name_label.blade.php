
    <div class="d-flex align-items-center">
        <!-- Optional: Initials Avatar for a pro look -->
        <div class="avatar-xs me-2 d-none d-md-block">
            <span class="avatar-title rounded-circle bg-light text-primary small fw-bold" style="padding: 5px; font-size: 0.7rem;">
                {{ substr($emp['full_name'], 0, 1) }}{{ substr($emp['full_name'], strpos($emp['full_name'], ' ') + 1, 1) }}
            </span>
        </div>
        
        <div>
            <!-- Short Name -->
            <div class="fw-bold text-dark text-nowrap" title="{{ $emp['full_name'] }}">
                {{ $emp['display_name'] }}
            </div>
            <!-- Employee ID underneath -->
            <div class="text-muted" style="font-size: 0.75rem;">
                ID: <span class="fw-medium">{{ $emp['emp_number'] }}</span>
            </div>
        </div>
    </div>

