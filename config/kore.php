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

];
