{{-- Invitation Email --}}
@component('mail::message')

# You've been invited to join {{ $companyName }}

@if($invitation->message)
> {{ $invitation->message }}
@endif

**Role:** {{ $invitation->role ?? 'Member' }}

@component('mail::button', ['url' => $acceptUrl])
Accept Invitation
@endcomponent

This invitation expires on **{{ $expiresAt ? $expiresAt->format('F j, Y \a\t g:i A') : 'N/A' }}**.

If you did not expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ $companyName }}

@endcomponent