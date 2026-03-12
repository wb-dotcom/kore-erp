<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /** Redirect to Google OAuth consent screen. */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /** Handle callback from Google. */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Google authentication failed. Please try again.']);
        }

        // Find existing user by email
        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'No account found for ' . $googleUser->getEmail() . '. Please contact your administrator to set up access.',
            ]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact your administrator.',
            ]);
        }

        // Store Google ID if not already set
        if (! $user->google_id) {
            $user->update(['google_id' => $googleUser->getId()]);
        }

        Auth::login($user, true);
        $user->update(['last_login' => now()]);
        ActivityLog::record('login', 'auth', $user->id, 'Logged in via Google');

        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
