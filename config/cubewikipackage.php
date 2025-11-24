<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your WikiCube API instance
    |
    */
    'api_url' => env('WIKICUBE_API_URL', 'https://wikicube.test'),

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | Your WikiCube API authentication token
    |
    */
    'api_token' => env('WIKICUBE_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | How long to cache the API responses (in minutes)
    |
    */
    'default_application' => env('WIKICUBE_APPLICATION_NAME', null),
    'cache_duration' => env('WIKICUBE_CACHE_DURATION', 5),
];
