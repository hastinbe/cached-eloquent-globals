<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) to cache global variables. Default is 24 hours.
    | Set to null to use Laravel's default cache TTL.
    |
    */

    'cache_duration' => env('CACHED_GLOBALS_DURATION', 86400), // 24 hours

    /*
    |--------------------------------------------------------------------------
    | Excluded Handles
    |--------------------------------------------------------------------------
    |
    | List of global set handles that should NOT be cached. By default,
    | all globals are cached for performance. Add handles here to exclude
    | them from caching if needed.
    |
    | Example: ['handle1', 'handle2']
    |
    | You can also set this via .env:
    | CACHED_GLOBALS_EXCLUDE=handle1,handle2
    |
    */

    'exclude_handles' => env('CACHED_GLOBALS_EXCLUDE', '')
        ? explode(',', env('CACHED_GLOBALS_EXCLUDE', ''))
        : [],

];

