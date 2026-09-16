<?php

return [
    'max_mb' => (int) env('UPLOAD_MAX_MB', 20),
    'disk' => BASE_PATH . '/storage/uploads',
];
