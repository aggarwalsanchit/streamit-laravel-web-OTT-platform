<?php
return [
    'secret_key'      => env('VIDEO_SECRET_KEY', 'my-default-secret'),
    'max_age'         => env('VIDEO_MAX_AGE', 86400),       // 24 h — outer hard cap

    // Short Drama stream proxy token TTL (tighter than max_age)
    'stream_ttl'      => env('SHORT_DRAMA_STREAM_TTL', 7200), // 2 hours

    // Bunny CDN Token Authentication key
    // Set this in Bunny dashboard: Zone → Security → Token Authentication
    'bunny_token_key' => env('BUNNY_VIDEO_KEY', ''),
];