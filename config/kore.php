<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kore ERP Application Configuration
    |--------------------------------------------------------------------------
    */

    'version' => '1.0.0',

    'company_name' => env('KORE_COMPANY_NAME', 'Your Company'),

    'currency_symbol' => env('KORE_CURRENCY_SYMBOL', '$'),

    'date_format' => env('KORE_DATE_FORMAT', 'm/d/Y'),

    'fiscal_year_start' => env('KORE_FISCAL_YEAR_START', '01'),

    'invoice_prefix' => env('KORE_INVOICE_PREFIX', 'INV-'),

    'timesheet_period' => env('KORE_TIMESHEET_PERIOD', 'weekly'),

    // ── Kore AI ──────────────────────────────────────────────────────────────
    'ai' => [
        'enabled'       => env('KORE_AI_ENABLED', true),
        'default_model' => env('KORE_AI_DEFAULT_MODEL', 'llama3'),
        'history_turns' => env('KORE_AI_HISTORY_TURNS', 8),
        'enabled_models'=> env('KORE_AI_ENABLED_MODELS', 'llama3,mistral,phi3:mini'),

        // Model catalogue shown in the setup guide (not enforced — anything in Ollama works)
        'recommended_models' => [
            'phi3:mini'      => ['desc' => 'Ultra-fast, simple queries',   'size' => '2.3GB'],
            'llama3:8b'      => ['desc' => 'Best balance (recommended)',    'size' => '4.7GB'],
            'mistral:7b'     => ['desc' => 'Strong structured analysis',    'size' => '4.1GB'],
            'deepseek-r1:8b' => ['desc' => 'Excellent chain-of-thought',    'size' => '4.9GB'],
            'mixtral:8x7b'   => ['desc' => 'Very capable (needs 24GB RAM)', 'size' => '26GB'],
            'llama3:70b'     => ['desc' => 'Highest quality (needs 40GB)',  'size' => '40GB'],
        ],
    ],

];
