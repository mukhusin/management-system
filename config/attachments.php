<?php

/*
|--------------------------------------------------------------------------
| Attachment upload rules
|--------------------------------------------------------------------------
*/

return [

    'disk' => env('ATTACHMENTS_DISK', env('FILESYSTEM_DISK', 'local')),

    // Max upload size in kilobytes (default 20 MB).
    'max_kb' => (int) env('ATTACHMENTS_MAX_KB', 20480),

    // Allowed extensions (Laravel "mimes" rule).
    'extensions' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'webp',
    ],
];
