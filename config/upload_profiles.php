<?php

return [
    'dangerous_extensions' => [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'exe', 'sh', 'bash', 'py', 'rb', 'pl', 'cgi', 'asp', 'aspx',
        'jsp', 'js', 'htaccess', 'htpasswd',
        'sql', 'sqlite', 'db', 'env', 'xml', 'yaml', 'yml', 'bat', 'cmd', 'com', 'vbs',
    ],

    'profiles' => [
        'avatar' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'max_kb' => 5120,
            'content_checks' => ['image_decode'],
        ],
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'],
            'max_kb' => 10240,
            'content_checks' => ['image_decode'],
        ],
        'video' => [
            'extensions' => ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'm4v', 'mpg', 'mpeg'],
            'mime_types' => [
                'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv',
                'video/x-flv', 'video/webm', 'video/x-matroska', 'video/matroska', 'video/3gpp',
                'video/mp2t', 'video/mpeg',
            ],
            // 10 GB limit (in KB) for large movie uploads
            'max_kb' => 10485760,
            'content_checks' => [],
        ],
        'audio' => [
            'extensions' => ['aac', 'm4a', 'mp3'],
            'mime_types' => ['audio/aac', 'audio/mp4', 'audio/mpeg', 'audio/x-m4a'],
            'max_kb' => 51200,
            'content_checks' => [],
        ],
        'subtitle' => [
            'extensions' => ['srt', 'vtt'],
            'mime_types' => ['text/plain', 'application/octet-stream'],
            'max_kb' => 10240,
            'content_checks' => ['text_utf8'],
        ],
        'document' => [
            'extensions' => ['json'],
            'mime_types' => ['application/json', 'text/plain'],
            'max_kb' => 1024,
            'content_checks' => ['json_valid'],
        ],
        'import' => [
            'extensions' => ['csv', 'xlsx', 'xls', 'txt'],
            'mime_types' => [
                'text/csv', 'text/plain',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'max_kb' => 10240,
            'content_checks' => [],
        ],
    ],
];
