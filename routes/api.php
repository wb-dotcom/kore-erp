<?php

use App\Http\Controllers\Api\InboundEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kore ERP — API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by bootstrap/app.php and are prefixed with /api.
| All routes here are stateless and do not use web session middleware.
|
*/

// ── Postmark Inbound Email Webhook ─────────────────────────────────────────────
//
// Configure in Postmark > Inbound > Settings > Webhook URL:
//   https://yourdomain.com/api/webhooks/inbound-email
//
// Add header:
//   Authorization: Bearer {INBOUND_EMAIL_SECRET from .env}
//
// Postmark POST spec: https://postmarkapp.com/developer/webhooks/inbound-webhook
//
Route::post('webhooks/inbound-email', [InboundEmailController::class, 'receive'])
    ->middleware('postmark.webhook')
    ->name('webhooks.inbound-email');
