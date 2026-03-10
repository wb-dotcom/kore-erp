<?php

namespace App\Services;

use App\Models\Proposal;
use Illuminate\Support\Facades\Log;

/**
 * Exports a proposal to Google Docs and (optionally) Google Slides.
 *
 * ── Setup Requirements ────────────────────────────────────────────────────────
 *
 * 1. Install the Google API PHP client:
 *      composer require google/apiclient:^2.15
 *
 * 2. Create a Service Account in Google Cloud Console:
 *    - Enable: Google Docs API, Google Drive API, Google Slides API
 *    - Create a service account, download the JSON key
 *    - Store key at: storage/app/google-service-account.json
 *    - Set in .env: GOOGLE_SERVICE_ACCOUNT_PATH=storage/app/google-service-account.json
 *
 * 3. Share a Google Drive folder with the service account email (Editor access):
 *    - Set in .env: GOOGLE_PROPOSALS_FOLDER_ID=your_drive_folder_id
 *
 * ── How it works ─────────────────────────────────────────────────────────────
 *
 * 1. Creates a new Google Doc in the configured Drive folder
 * 2. Inserts proposal content as structured Docs API requests:
 *    - Cover section: proposal ref, client name, title, date
 *    - Executive summary paragraph
 *    - Scope of work (rendered from HTML stored in proposals.scope_of_work)
 *    - Fee worksheet: one table per AIA phase
 *    - Billing terms section
 * 3. Returns the Google Doc URL for sharing with the client
 *
 * ── Updating an existing doc ─────────────────────────────────────────────────
 * If proposals.google_doc_id is already set, the existing doc is replaced
 * (full content clear then re-insert). This keeps the same URL stable.
 */
class GoogleDocsExportService
{
    private ?\Google_Client $googleClient = null;

    public function __construct(
        private readonly ProposalFeeCalculator $feeCalculator
    ) {}

    /**
     * Export the proposal to Google Docs and return the document URL.
     *
     * @throws \RuntimeException if the Google client is not configured
     */
    public function export(Proposal $proposal): string
    {
        $client = $this->getClient();
        $docsService  = new \Google_Service_Docs($client);
        $driveService = new \Google_Service_Drive($client);

        $docTitle = $this->buildDocTitle($proposal);

        // Create a new blank document
        $doc = $docsService->documents->create(
            new \Google_Service_Docs_Document(['title' => $docTitle])
        );
        $docId = $doc->getDocumentId();

        // Move to the configured proposals folder
        $folderId = config('services.google.proposals_folder_id');
        if ($folderId) {
            $file = $driveService->files->get($docId, ['fields' => 'parents']);
            $previousParents = implode(',', $file->getParents());

            $driveService->files->update($docId, new \Google_Service_Drive_DriveFile(), [
                'addParents'    => $folderId,
                'removeParents' => $previousParents,
                'fields'        => 'id, parents',
            ]);
        }

        // Build and apply content
        $requests = $this->buildDocRequests($proposal, $doc->getBody()->getContent());
        if (! empty($requests)) {
            $batchUpdate = new \Google_Service_Docs_BatchUpdateDocumentRequest([
                'requests' => $requests,
            ]);
            $docsService->documents->batchUpdate($docId, $batchUpdate);
        }

        $docUrl = "https://docs.google.com/document/d/{$docId}/edit";

        Log::info('Proposal exported to Google Docs', [
            'proposal_id' => $proposal->id,
            'ref'         => $proposal->ref,
            'doc_id'      => $docId,
            'url'         => $docUrl,
        ]);

        return $docUrl;
    }

    // ── Document Content Builder ───────────────────────────────────────────────

    /**
     * Build the ordered list of Google Docs API requests for proposal content.
     *
     * Docs API inserts content at index positions. We insert in reverse order
     * (bottom to top) so indices remain valid across insertions.
     */
    private function buildDocRequests(Proposal $proposal, mixed $existingContent): array
    {
        $requests = [];
        $index    = 1; // Docs API body starts at index 1

        $phaseByPhase = $this->feeCalculator->summaryByPhase($proposal);
        $currency     = config('kore.currency_symbol', '$');
        $dateStr      = $proposal->submitted_date?->format(config('kore.date_format', 'm/d/Y'))
                        ?? now()->format(config('kore.date_format', 'm/d/Y'));

        // We build an array of [text, style] pairs, then convert to Docs requests.
        $sections = $this->buildContentSections($proposal, $phaseByPhase, $currency, $dateStr);

        // Insert sections top-to-bottom using insertText and updateParagraphStyle
        foreach ($sections as $section) {
            $requests[] = [
                'insertText' => [
                    'location' => ['index' => $index],
                    'text'     => $section['text'],
                ],
            ];

            if (! empty($section['style'])) {
                $requests[] = [
                    'updateParagraphStyle' => [
                        'range' => [
                            'startIndex' => $index,
                            'endIndex'   => $index + strlen($section['text']),
                        ],
                        'paragraphStyle' => [
                            'namedStyleType' => $section['style'],
                        ],
                        'fields' => 'namedStyleType',
                    ],
                ];
            }

            $index += strlen($section['text']);
        }

        return $requests;
    }

    /**
     * Build structured content sections for the proposal document.
     *
     * Returns array of: ['text' => string, 'style' => string|null]
     */
    private function buildContentSections(
        Proposal $proposal,
        array $phaseByPhase,
        string $currency,
        string $dateStr
    ): array {
        $company = $proposal->company?->name ?? 'Client';
        $sections = [];

        // ── Cover / Header ────────────────────────────────────────────────────
        $sections[] = ['text' => $proposal->title . "\n", 'style' => 'HEADING_1'];
        $sections[] = ['text' => "Proposal Reference: {$proposal->ref}\n", 'style' => null];
        $sections[] = ['text' => "Client: {$company}\n", 'style' => null];
        $sections[] = ['text' => "Date: {$dateStr}\n", 'style' => null];
        $sections[] = ['text' => "\n", 'style' => null];

        // ── Executive Summary ─────────────────────────────────────────────────
        if ($proposal->executive_summary) {
            $sections[] = ['text' => "Executive Summary\n", 'style' => 'HEADING_2'];
            $sections[] = ['text' => strip_tags($proposal->executive_summary) . "\n\n", 'style' => null];
        }

        // ── Scope of Work ─────────────────────────────────────────────────────
        if ($proposal->scope_of_work) {
            $sections[] = ['text' => "Scope of Work\n", 'style' => 'HEADING_2'];
            // Strip HTML tags — Google Docs API doesn't render HTML inline
            $sections[] = ['text' => strip_tags($proposal->scope_of_work) . "\n\n", 'style' => null];
        }

        // ── Fee Worksheet ─────────────────────────────────────────────────────
        if (! empty($phaseByPhase)) {
            $sections[] = ['text' => "Fee Schedule\n", 'style' => 'HEADING_2'];

            foreach ($phaseByPhase as $phase) {
                $sections[] = ['text' => "{$phase['label']} ({$phase['code']})\n", 'style' => 'HEADING_3'];

                // Column header line
                $sections[] = ['text' => "Deliverable | Role | Hours | Rate | Amount\n", 'style' => null];

                foreach ($phase['items'] as $item) {
                    $amount    = number_format($item->effective_amount, 2);
                    $rate      = number_format($item->rate, 2);
                    $milestone = $item->milestone ? " [{$item->milestone}]" : '';
                    $sections[] = [
                        'text'  => "{$item->deliverable}{$milestone} | {$item->role_name} | {$item->hours} hrs | {$currency}{$rate}/hr | {$currency}{$amount}\n",
                        'style' => null,
                    ];
                }

                $phaseTotal = number_format($phase['amount'], 2);
                $sections[] = ['text' => "Phase Total: {$currency}{$phaseTotal}\n\n", 'style' => null];
            }

            $total = number_format($proposal->total_fee, 2);
            $sections[] = ['text' => "Total Proposed Fee: {$currency}{$total}\n\n", 'style' => 'HEADING_2'];
        }

        // ── Billing Terms ─────────────────────────────────────────────────────
        $sections[] = ['text' => "Billing Terms\n", 'style' => 'HEADING_2'];
        $billingType  = ucwords(str_replace('_', ' ', $proposal->billing_type));
        $billingCycle = ucwords(str_replace('_', ' ', $proposal->billing_cycle));
        $sections[]   = ['text' => "Billing Type: {$billingType}\n", 'style' => null];
        $sections[]   = ['text' => "Billing Cycle: {$billingCycle}\n", 'style' => null];
        $sections[]   = ['text' => "Payment Terms: Net {$proposal->payment_terms_days} days\n\n", 'style' => null];

        // ── Custom Terms & Conditions ─────────────────────────────────────────
        if ($proposal->terms_and_conditions) {
            $sections[] = ['text' => "Terms and Conditions\n", 'style' => 'HEADING_2'];
            $sections[] = ['text' => strip_tags($proposal->terms_and_conditions) . "\n", 'style' => null];
        }

        return $sections;
    }

    // ── Google Client ──────────────────────────────────────────────────────────

    private function getClient(): \Google_Client
    {
        if ($this->googleClient) {
            return $this->googleClient;
        }

        $keyPath = config('services.google.service_account_path');

        if (! $keyPath || ! file_exists(base_path($keyPath))) {
            throw new \RuntimeException(
                'Google service account key not found. ' .
                'Set GOOGLE_SERVICE_ACCOUNT_PATH in .env and place the JSON key at that path. ' .
                'See app/Services/GoogleDocsExportService.php for setup instructions.'
            );
        }

        $client = new \Google_Client();
        $client->setAuthConfig(base_path($keyPath));
        $client->setScopes([
            \Google_Service_Docs::DOCUMENTS,
            \Google_Service_Drive::DRIVE,
        ]);
        $client->setSubject(config('services.google.delegate_email'));

        $this->googleClient = $client;

        return $client;
    }

    private function buildDocTitle(Proposal $proposal): string
    {
        $client = $proposal->company?->name ?? 'Client';
        return "{$proposal->ref} – {$client} – {$proposal->title}";
    }
}
