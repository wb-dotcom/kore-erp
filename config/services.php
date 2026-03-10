<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token'          => env('POSTMARK_TOKEN'),
        // The shared secret sent by Postmark as: Authorization: Bearer {secret}
        // Set this in Postmark > Inbound > Webhook > Headers
        'webhook_secret' => env('INBOUND_EMAIL_SECRET'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // ── Google Workspace (Phase 1.5 — Proposal Export) ──────────────────────
    // Requires: composer require google/apiclient:^2.15
    // Docs: https://developers.google.com/docs/api/quickstart/php
    'google' => [
        // Path to the service account JSON key (relative to project root)
        'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH', 'storage/app/google-service-account.json'),
        // Optional: email to impersonate (Domain-Wide Delegation)
        'delegate_email'       => env('GOOGLE_DELEGATE_EMAIL'),
        // Google Drive folder ID where proposals are created
        'proposals_folder_id'  => env('GOOGLE_PROPOSALS_FOLDER_ID'),
    ],

    // ── Ollama (Phase 3 — Local LLM) ────────────────────────────────────────
    'ollama' => [
        'host'        => env('OLLAMA_HOST', 'http://host.docker.internal:11434'),
        'embed_model' => env('OLLAMA_EMBED_MODEL', 'nomic-embed-text'),
        'chat_model'  => env('OLLAMA_CHAT_MODEL', 'llama3'),
    ],

];
