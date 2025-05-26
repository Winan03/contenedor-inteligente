<?php

return [
    'credentials' => base_path(env('FIREBASE_CREDENTIALS', 'firebase_credentials.json')),
    'database_url' => env('FIREBASE_DATABASE_URL'),
];
