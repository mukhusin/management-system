<?php

/*
|--------------------------------------------------------------------------
| Generic tracker (EMREC Master Business Tracker) + team import
|--------------------------------------------------------------------------
*/

return [

    // Prefix for auto-generated tracker item references (EMREC-001, ...).
    'reference_prefix' => env('TRACKER_REFERENCE_PREFIX', 'EMREC'),

    // Password given to member accounts created by UserSeeder.
    'seed_member_password' => env('TEAM_MEMBER_PASSWORD', 'password'),

    'import' => [
        // Spreadsheet "Responsible Person" text => user email.
        'people' => [
            'Maalim' => 'maalim@emrec.co.tz',
            'Mukhusin' => 'backend1developer@gmail.com',
            'Dr. Simba' => 'simba@emrec.co.tz',
            'Dr. Sanga' => 'sanga@emrec.co.tz',
        ],

        // Spreadsheet "Category" text => TrackerCategory value (case-insensitive).
        // "New Advertised Works" intentionally maps to `other` (live tenders come
        // from the Tenders module, not the generic tracker).
        'categories' => [
            'digital products' => 'digital_product',
            'e-commerce' => 'ecommerce',
            'agreements & partnerships' => 'partnership',
            'emrec affairs' => 'emrec_affairs',
            'new advertised works' => 'other',
        ],
    ],
];
