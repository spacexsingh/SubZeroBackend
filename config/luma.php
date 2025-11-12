<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Luma API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for integrating with the Luma API.
    |
    */

    'api_url' => env('LUMA_API_URL', 'https://public-api.luma.com'),

    'api_version' => env('LUMA_API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests to Luma.
    |
    */

    'timeout' => env('LUMA_API_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for retrying failed API requests.
    |
    */

    'retry' => [
        'times' => env('LUMA_API_RETRY_TIMES', 3),
        'sleep' => env('LUMA_API_RETRY_SLEEP', 1000), // milliseconds
    ],

];