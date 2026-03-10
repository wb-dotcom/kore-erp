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

    // ── Ollama (Phase 3 — Local LLM) ────────────────────────────────────────
    'ollama' => [
        'host'        => env('OLLAMA_HOST', 'http://host.docker.internal:11434'),
        'embed_model' => env('OLLAMA_EMBED_MODEL', 'nomic-embed-text'),
        'chat_model'  => env('OLLAMA_CHAT_MODEL', 'llama3'),
    ],

];
