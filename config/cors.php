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

    // Origins allowed to make requests. Use FRONTEND_URL env var by default
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],

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
