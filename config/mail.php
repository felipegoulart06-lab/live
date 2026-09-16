<?php

return [
    'mailer' => env('MAIL_MAILER', 'smtp'),
    'host' => env('MAIL_HOST', 'localhost'),
    'port' => (int) env('MAIL_PORT', 587),
    'username' => env('MAIL_USERNAME', ''),
    'password' => env('MAIL_PASSWORD', ''),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'from_address' => env('MAIL_FROM_ADDRESS', 'nao-responda@localhost'),
    'from_name' => env('MAIL_FROM_NAME', env('APP_NAME', 'CinquentaConto')),
];
