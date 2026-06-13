@props(['status'])

@php
    $badgeClass = match($status) {
        'draft'     => 'secondary',
        'pending'   => 'warning',
        'approved'  => 'success',
        'rejected'  => 'danger',
        'cancelled' => 'secondary',
        default     => 'secondary',
    };
    $icon = match($status) {
        'draft'     => 'fas fa-pen',
        'pending'   => 'fas fa-clock',
        'approved'  => 'fas fa-check-circle',
        'rejected'  => 'fas fa-times-circle',
        'cancelled' => 'fas fa-ban',
        default     => 'fas fa-question-circle',
    };
@endphp

<span class="badge bg-{{ $badgeClass }}">
    <i class="{{ $icon }} me-1"></i>
    {{ ucfirst($status) }}
</span>