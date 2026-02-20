<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * KORE ERP - Authentication Middleware
 * Protects routes from unauthenticated access
 * Also checks account is still active on every request
 */
class KoreAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to access Kore ERP.');
        }

        $user = Auth::user();

        // Check if user account is still active (could be deactivated by admin)
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        return $next($request);
    }
}
