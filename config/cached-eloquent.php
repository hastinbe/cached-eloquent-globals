<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Variables Caching
    |--------------------------------------------------------------------------
    |
    | Configuration for caching Eloquent-based global variables.
    | Global variables rarely change, so a longer cache duration is used.
    |
    */

    'globals' => [
        /*
        | Enable or disable global variables caching. By default, caching is enabled.
        | Set to false to disable caching if needed.
        */
        'enabled' => env('CACHED_GLOBALS_ENABLED', true),

        /*
        | Cache duration in seconds for global variables. Default is 24 hours.
        | Set to null to use Laravel's default cache TTL.
        */
        'cache_duration' => env('CACHED_GLOBALS_DURATION', 86400), // 24 hours

        /*
        | List of global set handles that should NOT be cached.
        | Example: ['handle1', 'handle2']
        |
        | You can also set this via .env:
        | CACHED_GLOBALS_EXCLUDE=handle1,handle2
        */
        'exclude_handles' => env('CACHED_GLOBALS_EXCLUDE', '')
            ? explode(',', env('CACHED_GLOBALS_EXCLUDE', ''))
            : [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Entry Caching
    |--------------------------------------------------------------------------
    |
    | Configuration for caching Eloquent-based entries.
    | Entries change more frequently, so a shorter cache duration is used.
    |
    */

    'entries' => [
        /*
        | Enable or disable entry caching. By default, caching is enabled.
        | Set to false to disable caching if needed.
        */
        'enabled' => env('CACHED_ENTRIES_ENABLED', true),

        /*
        | Cache duration in seconds for entry queries. Default is 5 minutes.
        | Entries change more frequently than globals, so use a shorter TTL.
        |
        | Recommended values:
        | - High-traffic, rarely-updated sites: 900 (15 minutes)
        | - Medium-traffic sites: 300 (5 minutes) - DEFAULT
        | - Frequently updated sites: 60-180 (1-3 minutes)
        */
        'cache_duration' => env('CACHED_ENTRIES_DURATION', 300), // 5 minutes

        /*
        | Collections that should NOT be cached. Useful for frequently updated
        | collections like "news", "events", or any collection with real-time data.
        |
        | Example: ['news', 'events', 'live_updates']
        |
        | You can also set this via .env:
        | CACHED_ENTRIES_EXCLUDE=news,events,live_updates
        */
        'exclude_collections' => env('CACHED_ENTRIES_EXCLUDE', '')
            ? explode(',', env('CACHED_ENTRIES_EXCLUDE', ''))
            : [],

        /*
        | When using Redis or Memcached (which support tags), you can choose to
        | only cache queries that can be tagged for easier invalidation.
        | This is recommended for better cache management.
        */
        'tagged_only' => env('CACHED_ENTRIES_TAGGED_ONLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fieldset Caching
    |--------------------------------------------------------------------------
    |
    | Configuration for caching Eloquent-based fieldsets.
    | Fieldsets are configuration that rarely changes, so a long cache is ideal.
    |
    */

    'fieldsets' => [
        /*
        | Enable or disable fieldset caching. By default, caching is enabled.
        | Set to false to disable caching if needed.
        */
        'enabled' => env('CACHED_FIELDSETS_ENABLED', true),

        /*
        | Cache duration in seconds for fieldsets. Default is 24 hours.
        | Fieldsets are configuration data that rarely changes.
        */
        'cache_duration' => env('CACHED_FIELDSETS_DURATION', 86400), // 24 hours
    ],

];

