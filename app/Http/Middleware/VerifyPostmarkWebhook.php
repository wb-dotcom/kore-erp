<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates inbound Postmark webhook requests.
 *
 * Postmark inbound email webhooks do not sign payloads with HMAC.
 * The standard security approach is bearer token validation.
 *
 * Configure in Postmark:
 *   Webhook URL: https://yourdomain.com/api/webhooks/inbound-email
 *   Add header:  Authorization: Bearer {INBOUND_EMAIL_SECRET}
 *
 * The secret is set in .env as INBOUND_EMAIL_SECRET.
 * Rotate it by updating the env var and the Postmark webhook header simultaneously.
 */
class VerifyPostmarkWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.postmark.webhook_secret');

        // If no secret is configured, allow in local/testing environments only.
        if (empty($secret)) {
            if (! app()->isLocal() && ! app()->runningUnitTests()) {
                return response()->json(['error' => 'Webhook not configured.'], 500);
            }
            return $next($request);
        }

        $provided = $request->bearerToken();

        if (! hash_equals($secret, (string) $provided)) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
