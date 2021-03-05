<?php

return [
    'hoc247net' => [
        'site' => 'hoc247net',
        'disk' => 'public',
        'folder' => 'hoc247'
    ],
    'khoahoc_vietjack' => [
        'site' => 'khoahoc_vietjack',
        'disk' => 'public',
        'folder' => 'khoahoc_vietjack'
    ],
    'loigiaihay' => [
        'site' => 'loigiaihay',
        'disk' => 'public',
        'folder' => 'loigiaihay'
    ],

    'api' => env('CRAWL_API', 'http://dev.cunghocvui.com/api/creawl/add-post'),
    'private_key' => env('CRAWL_PRIVATE_KEY', 'crawl_cunghocvui'),
];
