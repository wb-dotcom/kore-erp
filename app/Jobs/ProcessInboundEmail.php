<?php

namespace App\Jobs;

use App\Services\InboundEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes an inbound Postmark email payload asynchronously.
 *
 * The controller dispatches this job immediately and returns HTTP 200
 * to Postmark. Postmark's retry policy (max 25 retries over 72 hours)
 * only triggers on non-2xx responses, so we must not fail synchronously.
 *
 * On failure, the job is retried up to 3 times with exponential backoff.
 * After all retries are exhausted, failed() logs the error and preserves
 * the raw payload in the failed_jobs table for manual recovery.
 */
class ProcessInboundEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Max attempts before the job is moved to the failed_jobs table.
     */
    public int $tries = 3;

    /**
     * Retry after these delays (seconds) on failure.
     */
    public array $backoff = [30, 120, 300];

    /**
     * Prevent duplicate processing if the same MessageID is queued twice
     * (Postmark can send duplicate webhooks on network issues).
     */
    public int $uniqueFor = 300;

    public function __construct(
        private readonly array $payload
    ) {}

    public function handle(InboundEmailService $service): void
    {
        $service->process($this->payload);
    }

    /**
     * Called after all retries are exhausted.
     * Logs the failure — raw_payload is preserved in failed_jobs for recovery.
     */
    public function failed(Throwable $e): void
    {
        Log::error('ProcessInboundEmail: permanently failed', [
            'message_id' => $this->payload['MessageID'] ?? 'unknown',
            'from'       => $this->payload['From'] ?? 'unknown',
            'subject'    => $this->payload['Subject'] ?? 'unknown',
            'error'      => $e->getMessage(),
        ]);
    }

    /**
     * Unique ID based on Postmark's MessageID to prevent duplicate processing.
     */
    public function uniqueId(): string
    {
        return 'inbound_email_' . ($this->payload['MessageID'] ?? uniqid());
    }
}
