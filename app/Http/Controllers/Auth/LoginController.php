<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * KORE ERP - LoginController
 * Handles all authentication: login, logout, password reset
 */
class LoginController extends Controller
{
    // ─── Show Login Form ───────────────────────────────────────────
    public function showLogin(): View
    {
        return view('auth.login');
    }

    // ─── Process Login ────────────────────────────────────────────
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        // Check if user exists and is active
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email address.'])->withInput();
        }

        if (!$user->is_active) {
            return back()->withErrors(['email' => 'Your account has been deactivated. Please contact your administrator.'])->withInput();
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'The password you entered is incorrect.'])->withInput();
        }

        // Log the user in
        Auth::login($user, $remember);

        // Update last login timestamp
        $user->update(['last_login' => now()]);

        // Log activity
        ActivityLog::log('login', 'auth', $user->id, 'User logged in', $request->ip());

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // ─── Logout ──────────────────────────────────────────────────
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Log activity
        if ($userId) {
            ActivityLog::log('logout', 'auth', $userId, 'User logged out');
        }

        return redirect()->route('login')->with('success', 'You have been signed out successfully.');
    }

    // ─── Show Forgot Password Form ────────────────────────────────
    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    // ─── Send Password Reset Email ────────────────────────────────
    public function sendReset(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal if email exists
            return back()->with('success', 'If an account exists with that email, we sent a reset link.');
        }

        // Generate token
        $token = Str::random(64);

        DB::table('password_resets')->upsert(
            ['email' => $request->email, 'token' => Hash::make($token), 'created_at' => now()],
            ['email'],
            ['token', 'created_at']
        );

        // TODO: Dispatch password reset email (Session 3 — Email module)
        // Mail::to($user->email)->send(new PasswordResetMail($token));

        return back()->with('success', 'If an account exists with that email, we sent a reset link.');
    }

    // ─── Show Reset Password Form ─────────────────────────────────
    public function showReset(string $token): View
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    // ─── Process Password Reset ───────────────────────────────────
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $reset = DB::table('password_resets')
                   ->where('email', $request->email)
                   ->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        // Check token is not older than 60 minutes
        if (now()->diffInMinutes($reset->created_at) > 60) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'This password reset link has expired. Please request a new one.']);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No user found with this email.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        DB::table('password_resets')->where('email', $request->email)->delete();

        ActivityLog::log('password_reset', 'auth', $user->id, 'Password was reset');

        return redirect()->route('login')->with('success', 'Password reset successfully. Please log in with your new password.');
    }
}
