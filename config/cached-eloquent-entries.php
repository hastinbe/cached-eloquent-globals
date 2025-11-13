<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Entry Caching
    |--------------------------------------------------------------------------
    |
    | Enable or disable entry caching. By default, caching is only enabled
    | in production environments. Set to true to enable in all environments.
    |
    */

    'enabled' => env('CACHED_ENTRIES_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) to cache entry queries. Default is 5 minutes (300).
    | Entries change more frequently than globals, so use a shorter TTL.
    |
    | Recommended values:
    | - High-traffic, rarely-updated sites: 900 (15 minutes)
    | - Medium-traffic sites: 300 (5 minutes) - DEFAULT
    | - Frequently updated sites: 60-180 (1-3 minutes)
    |
    */

    'cache_duration' => env('CACHED_ENTRIES_DURATION', 300), // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Excluded Collections
    |--------------------------------------------------------------------------
    |
    | Collections that should NOT be cached. Useful for frequently updated
    | collections like "news", "events", or any collection with real-time data.
    |
    | Example: ['news', 'events', 'live_updates']
    |
    | You can also set this via .env:
    | CACHED_ENTRIES_EXCLUDE=news,events,live_updates
    |
    */

    'exclude_collections' => env('CACHED_ENTRIES_EXCLUDE', '')
        ? explode(',', env('CACHED_ENTRIES_EXCLUDE', ''))
        : [],

    /*
    |--------------------------------------------------------------------------
    | Cache Tagged Queries Only
    |--------------------------------------------------------------------------
    |
    | When using Redis or Memcached (which support tags), you can choose to
    | only cache queries that can be tagged for easier invalidation.
    | This is recommended for better cache management.
    |
    */

    'tagged_only' => env('CACHED_ENTRIES_TAGGED_ONLY', false),

];

