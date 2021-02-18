<?php


return [
    
    'disks' => [
        'download' => env('DISK_DOWNLOAD', 'local'),
        'sqlite_stack' => env('DISK_SQLITE_STACK'),
    ],
    'accepted_extensions' => [
        'zip','rar',
        'doc','docx',
        'ppt', 'pot', 'pps','pptx', 'potx', 'ppsx','pptm',
        'pdf',
        'odt','odp',
    ],
    'max_content_length' => 104857600, // 100MB
    'link_filter' => [
        'can_be_duplicated_filter' => [
            'patterns' => env('SITE_ALLOW_DUPLICATED') ? [ '/.*/' ] : [],
            'contains' => [
                '//wordpress.com',
                '//sites.google.com',
                '//sistemas.eel.usp.br',
            ]
        ],
        'dont_crawl_filter' => [
            'patterns' => [
//			    '/\w+\.gov\.\w+/',
//			    '/\w+\.go\.id/',
            ]
        ],
        'ignore_filter' =>[
            'patterns' => [
                '/^https?\:\/\/(\w+\.)+\w+\/?$/',
                '/google.*viewer.*&srcid\=/',
                '/javascript\:/i',
                '/mailto\:/i',
                '/\/tel\:/i',
                '/^tel\:/i',
            ],
            'contains' => [
                'facebook.com',
                'fb.me/',
                'instagram.com/',
                'plus.google.com/',
                'twitter.com/',
                'pinterest.com/',
                '.adobe.com/',
                'blogspot.com/',
                'blogger.com/',
                'menpan.go.id',
                'javascript%3A',// link call script function
                'javascript:',// link call script function
                'mailto:',// link call script function
                'mailto%3A',// link call script function
            ],
            'starts'=>[],
            'ends'=>[],
        ],
    ],
    'should_retry_status_codes' => [
        408, // Request Timeout
        429, // Too Many Requests
        509, // Bandwidth Limit Exceeded (Apache)
    ],
    'selector_types' => [
        'data'     => 'Data',
        'link'     => 'Link',
        'get_link' => 'Get Link Download',
        'click'    => 'Click',
        'keywords' => 'Keywords',
        'name' => 'Document name',
        'get_links' => 'Get multi part document links',
        'stop_click' => 'Stop click',
        'get_remote_title' => 'Remote title',
    ],
    'browsers' => [
        'phantomjs' => [
            'bin' => env( 'BIN_PHANTOMJS'),
        ],
        'guzzle' => [// Guzzle default config
            'verify' => false,
            'connect_timeout' => 15, // 15 seconds
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:64.0) Gecko/20100101 Firefox/64.0',
            ],
            'http_errors' => false,
        ],
    ],
    'version' => 'v2.0.' . now(),
];