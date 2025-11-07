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
    'api_url' => env('WIKICUBE_API_URL', 'http://wikicube.test'),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | How long to cache the API responses (in minutes)
    |
    */
    'cache_duration' => env('WIKICUBE_CACHE_DURATION', 5),
];

