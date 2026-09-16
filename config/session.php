<?php

return [
    'name' => env('SESSION_NAME', 'nexo_session'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'secure' => (bool) env('SESSION_SECURE', getenv('VERCEL') === '1'),
];
