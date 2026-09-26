<?php

return [
    'public_key' => (string) env('MP_PUBLIC_KEY', ''),
    'access_token' => (string) env('MP_ACCESS_TOKEN', ''),
    'webhook_secret' => (string) env('MP_WEBHOOK_SECRET', ''),
    'statement' => (string) env('MP_STATEMENT_DESCRIPTOR', 'CINQUENTACONTO'),
];
