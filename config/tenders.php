<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fetch schedule
    |--------------------------------------------------------------------------
    |
    | Cron expression for the scheduled `tenders:fetch` run (see
    | routes/console.php). Defaults to every 6 hours. Override with
    | TENDERS_FETCH_CRON in .env, e.g. "0 2 * * *" for once daily at 02:00.
    |
    */

    'fetch_cron' => env('TENDERS_FETCH_CRON', '0 */6 * * *'),

    /*
    |--------------------------------------------------------------------------
    | Default HTTP timeout (seconds) for source clients
    |--------------------------------------------------------------------------
    */

    'http_timeout' => (int) env('TENDERS_HTTP_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Sources
    |--------------------------------------------------------------------------
    |
    | Each source is skipped unless it is both `enabled` and has whatever
    | credentials/URL it needs. `tenders:fetch` reports which ones it ran
    | and which it skipped.
    |
    */

    'world_bank' => [
        'enabled' => (bool) env('WORLD_BANK_ENABLED', true),
        // Full-text term sent as `qterm`. Leave blank for every country.
        'query' => env('WORLD_BANK_QUERY', 'Tanzania'),
        'rows' => (int) env('WORLD_BANK_ROWS', 100),
    ],

    'reliefweb' => [
        'enabled' => (bool) env('RELIEFWEB_ENABLED', true),
        // Since 2025-11-01 ReliefWeb rejects unknown appnames with a 403.
        // Request one at https://apidoc.reliefweb.int/parameters#appname
        // then set it here. Blank = source is skipped.
        'appname' => env('RELIEFWEB_APPNAME'),
        'query' => env('RELIEFWEB_QUERY', 'tender OR procurement OR "request for proposal"'),
        'country' => env('RELIEFWEB_COUNTRY', 'Tanzania United Republic of'),
        'limit' => (int) env('RELIEFWEB_LIMIT', 50),
    ],

    'taneps' => [
        'enabled' => (bool) env('TANEPS_ENABLED', true),
        // TANePS has no public API and the JS dashboard can't be scraped
        // server-side. Point this at the classic server-rendered listing
        // (e.g. https://www.taneps.go.tz/epps/common/viewOpenedTenders.do)
        // once you've confirmed the URL and row markup. Blank = skipped.
        'listing_url' => env('TANEPS_LISTING_URL'),
    ],

];
