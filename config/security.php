<?php

return [
    'login_max' => (int) env('RATE_LIMIT_LOGIN', 8),
    'window' => (int) env('RATE_LIMIT_WINDOW', 300),
];
