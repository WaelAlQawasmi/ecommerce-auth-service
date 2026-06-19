<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Versioning
    |--------------------------------------------------------------------------
    |
    | The active API version exposed to clients. Route files live under
    | routes/api/{version}.php and controllers under App\Http\Controllers\Api\{Version}.
    |
    */

    'version' => env('API_VERSION', 'v1'),

    'prefix' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Documentation
    |--------------------------------------------------------------------------
    */

    'documentation' => [
        'enabled' => env('API_DOCS_ENABLED', true),
        'path' => 'docs/api',
    ],

];
