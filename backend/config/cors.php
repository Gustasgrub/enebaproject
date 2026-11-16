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
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Only our frontend may call the API from a browser. Auth uses bearer tokens, not cookies,
    // so credentials are not needed (supports_credentials stays false).
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // How long a browser may reuse a preflight (OPTIONS) answer, in seconds. Requests with an
    // Authorization header or a JSON body need a preflight; caching it saves a round trip to the
    // free-tier server on each such request. Chrome caps the value at 2 hours.
    'max_age' => 7200,

    'supports_credentials' => false,

];
