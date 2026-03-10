<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInboundEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives inbound email payloads from Postmark.
 *
 * Critical: this controller must return HTTP 200 within Postmark's 30-second
 * timeout. Processing is delegated entirely to the ProcessInboundEmail job.
 * If Postmark receives anything other than 2xx, it will retry up to 25 times
 * over 72 hours — so we never let processing failures bubble up here.
 */
class InboundEmailController extends Controller
{
    public function receive(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Minimal validation: Postmark always sends From and Subject.
        // We don't abort on missing fields — the job handles graceful degradation.
        if (empty($payload['From'])) {
            Log::warning('InboundEmail webhook: missing From field', [
                'ip' => $request->ip(),
            ]);
        }

        // Dispatch to queue and immediately acknowledge receipt.
        // The job has deduplication via MessageID to prevent double-processing.
        ProcessInboundEmail::dispatch($payload);

        Log::info('InboundEmail webhook: queued for processing', [
            'message_id' => $payload['MessageID'] ?? 'unknown',
            'from'       => $payload['From'] ?? 'unknown',
            'subject'    => $payload['Subject'] ?? '(no subject)',
        ]);

        // Postmark expects a 200 OK. Any other code triggers a retry.
        return response()->json(['status' => 'queued'], 200);
    }
}
