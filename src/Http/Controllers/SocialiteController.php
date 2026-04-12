<?php

namespace QuickerFaster\UILibrary\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Routing\Controller;

class SocialiteController extends Controller
{
    /**
     * Redirect to the social provider.
     */
    public function redirect($provider)
    {
        if (!config("quicker-faster-ui.socialite.providers.{$provider}.enabled", false)) {
            abort(404);
        }

        $redirectUrl = route('socialite.callback', ['provider' => $provider]);

        return Socialite::driver($provider)
            ->redirectUrl($redirectUrl)  // Set the custom redirect URL
            ->redirect();                 // Then generate the redirect response
    }

    /**
     * Handle callback from provider.
     */

    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->redirectUrl(url('/auth/' . $provider . '/callback'))
                ->user();
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Socialite callback error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'msg' => 'Unable to login with ' . $provider . '. Please try again.'
            ]);
        }

        // Dynamically get the User model class from the main app config
        $userModel = config('auth.providers.users.model');

        // Find existing user by email
        $user = $userModel::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            // Create a new user using forceCreate to bypass fillable protection
            $user = $userModel::forceCreate([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Social User',
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(24)), // Random password, user can reset later
                'email_verified_at' => now(), // Social emails are typically verified
            ]);
        }

        // Log the user in and "remember" them
        Auth::login($user, true);

        // Redirect to Fortify's home or the intended URL
        return redirect()->intended(config('fortify.home', '/dashboard'));
    }
}
