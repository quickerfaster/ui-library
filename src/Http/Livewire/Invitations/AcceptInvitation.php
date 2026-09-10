<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Invitations;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\Invitation;
use QuickerFaster\UILibrary\Services\Invitations\InvitationService;
use Illuminate\Support\Facades\Auth;

class AcceptInvitation extends Component
{
    public ?string $token = null;

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $name = null;

    public ?string $email = null;

    public ?string $error = null;

    public bool $isValid = false;

    public bool $isAccepted = false;

    protected InvitationService $invitationService;

    public function boot(InvitationService $invitationService): void
    {
        $this->invitationService = $invitationService;
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        $invitation = $this->invitationService->findValidByToken($token);

        if (! $invitation) {
            // Check if it exists but is expired/accepted/revoked
            $existing = Invitation::where('token', $token)->first();

            if ($existing) {
                $this->error = match ($existing->status) {
                    'expired' => 'This invitation has expired.',
                    'accepted' => 'This invitation has already been accepted.',
                    'revoked' => 'This invitation has been revoked.',
                    default => 'This invitation is no longer valid.',
                };
            } else {
                $this->error = 'Invalid invitation link.';
            }

            return;
        }

        $this->isValid = true;
        $this->email = $invitation->email;
    }

    public function accept()
    {
        $this->validate([
            'password' => 'required|min:8|confirmed',
            'name' => 'nullable|string|max:255',
        ]);

        $invitation = $this->invitationService->accept(
            $this->token,
            $this->password,
            $this->name
        );

        if (! $invitation) {
            $this->error = 'Unable to accept invitation. It may have expired.';

            return;
        }

        $this->isAccepted = true;

        // Log the user in
        $userModel = config('auth.providers.users.model');
        $user = $userModel::where('email', $invitation->email)->first();

        if ($user) {
            Auth::login($user);

            // Clear any stale company selection from a previous session
            // (e.g., super admin's "All Companies" mode)
            session()->forget('current_company_id');

            // Phase 7: Post-acceptance onboarding — redirect to the
            // consolidated onboarding wizard if the consuming app has
            // registered HR onboarding steps via Spatie Onboard.
            //
            // The HR module now registers a single "Employee Onboarding"
            // step pointing to the /onboarding wizard route. The wizard
            // component manages its own internal sub-step state.
            //
            // Link resolution:
            //   - HR onboarding: stored as "/onboarding" (raw URL path)
            //     → falls through to url() after route() fails
            //   - app_onboarding defaults: e.g. "/my-profile"
            //     → same fallback behavior
            if (method_exists($user, 'onboarding')) {
                $onboarding = $user->onboarding();

                if ($onboarding->inProgress()) {
                    $nextStep = $onboarding->nextUnfinishedStep();

                    if ($nextStep && $nextStep->link) {
                        // Resolve as named route first (library defaults),
                        // fall back to raw URL (HR wizard /onboarding).
                        try {
                            $this->redirect(route($nextStep->link));
                        } catch (\Exception $e) {
                            $this->redirect(url($nextStep->link));
                        }

                        return;
                    }
                }
            }

            // No onboarding steps configured (or all complete) — go home.
            $this->redirect(route(config('ui-library.home_route', 'admin.dashboard')));

            return;
        }
    }

    public function render()
    {
        return view('qf::invitations.accept')
            ->layout('qf::layouts.guest');
    }
}