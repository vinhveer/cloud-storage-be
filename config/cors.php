<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    // Paths that should be allowed to perform CORS requests
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Methods allowed for CORS
    'allowed_methods' => ['*'],

    // Origins allowed to make requests. Always allow local dev and production frontend
    'allowed_origins' => [
        env('FRONTEND_URL', 'https://cloudfe.nguyenquangvinh.id.vn'),
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5174',
        'https://cloudfe.nguyenquangvinh.id.vn',
    ],

    // Patterns to match allowed origins
    'allowed_origins_patterns' => [],

    // Request headers allowed
    'allowed_headers' => ['*'],

    // Headers exposed to the browser
    'exposed_headers' => ['Content-Length', 'Authorization'],

    // How long (in seconds) the results of a preflight request can be cached
    'max_age' => 0,

    // Whether the response to the request can be exposed when the credentials flag is true
    'supports_credentials' => true,
];
