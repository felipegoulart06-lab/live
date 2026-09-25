<?php

return [
    'image_max_mb' => (int) env('UPLOAD_IMAGE_MAX_MB', 5),
    'file_max_mb' => (int) env('UPLOAD_FILE_MAX_MB', 10),
    'supabase_url' => rtrim((string) env('SUPABASE_URL', ''), '/'),
    'supabase_key' => (string) env('SUPABASE_SERVICE_ROLE_KEY', ''),
    'public_bucket' => (string) env('SUPABASE_PUBLIC_BUCKET', 'public-media'),
    'private_bucket' => (string) env('SUPABASE_PRIVATE_BUCKET', 'private-files'),
];
