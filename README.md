# Cached Eloquent Repositories

A Statamic addon that provides automatic caching for Eloquent-based global variables and entries to improve performance.

## Features

### Global Variables Caching
- **Long-term Caching**: 24-hour cache (configurable)
- **Automatic Invalidation**: Cache cleared when globals are saved
- **Configurable**: Exclude specific handles or adjust cache duration

### Entry Caching
- **Smart Query Caching**: Short-term caching (5 minutes default)
- **Redis Tag Support**: Intelligent cache tagging when using Redis/Memcached
- **Automatic Fallback**: Works with any cache driver, optimized for Redis
- **Collection-Level Control**: Exclude frequently-updated collections
- **Automatic Invalidation**: Cache cleared on save/delete

### General
- **Zero Configuration**: Works out of the box with sensible defaults
- **Event-Driven**: Listens to Statamic events for automatic cache clearing
- **PostgreSQL Optimized**: Designed for high-performance PostgreSQL setups

> **⚠️ Important**: This addon depends on the `statamic/eloquent-driver` package. It only works when your repositories are set to use Eloquent drivers in the `config/statamic/eloquent-driver.php` configuration file.

## Installation

```bash
composer require hastinbe/cached-eloquent-globals
```

## Configuration

Publish the config files (optional):

```bash
# Global variables config
php artisan vendor:publish --tag=cached-eloquent-globals-config

# Entry caching config
php artisan vendor:publish --tag=cached-eloquent-entries-config
```

### Global Variables Configuration

```env
# Cache duration in seconds (default: 86400 = 24 hours)
CACHED_GLOBALS_DURATION=86400

# Comma-separated list of handles to exclude from caching
CACHED_GLOBALS_EXCLUDE=handle1,handle2
```

```php
// config/cached-eloquent-globals.php
return [
    'cache_duration' => 86400, // 24 hours
    'exclude_handles' => ['some_handle'],
];
```

### Entry Caching Configuration

```env
# Enable entry caching (default: production only)
CACHED_ENTRIES_ENABLED=true

# Cache duration in seconds (default: 300 = 5 minutes)
CACHED_ENTRIES_DURATION=300

# Comma-separated list of collections to exclude
CACHED_ENTRIES_EXCLUDE=news,live_updates,events
```

```php
// config/cached-eloquent-entries.php
return [
    'enabled' => true,
    'cache_duration' => 300, // 5 minutes
    'exclude_collections' => ['news', 'events'],
    'tagged_only' => false, // Only cache when tags are supported
];
```

## How It Works

### Global Variables
1. **Caching**: Results are cached with a 24-hour TTL by default
2. **Invalidation**: Cache is automatically cleared when variables are saved

### Entry Caching
1. **Smart Detection**: Automatically detects if Redis/Memcached is available for tag support
2. **Tagged Caching** (Redis/Memcached):
   - Entries cached with hierarchical tags: `entries`, `collection:X`, `entry:Y`, `uris`
   - URI lookups are also cached and tagged for efficient invalidation
   - Efficient bulk invalidation when collections or entries are updated
3. **Key-Based Caching** (File/Database drivers):
   - Falls back to individual cache key management
   - Still provides automatic invalidation on save/delete
   - Multi-site URI cache clearing for non-tagged environments
4. **Short TTL**: Default 5-minute cache to balance freshness with performance
5. **Cached Methods**:
   - `whereInId()` - Entry lookups by ID (useful for navigation, listings)
   - `findByUri()` - Entry lookups by URI (critical for page rendering)
   - Automatic invalidation on `save()`, `delete()`, and `updateUris()`

## Manual Cache Clearing

### Global Variables

```php
use Statamic\Facades\GlobalSet;

// Clear cache for a specific handle
$repository = app(\Statamic\Contracts\Globals\GlobalVariablesRepository::class);
$repository->clearCache('advertising');

// Clear cache for all cached handles
$repository->clearAllCache();
```

### Entries

```php
use Statamic\Contracts\Entries\EntryRepository;

// Clear cache for a specific collection
$repository = app(EntryRepository::class);
$repository->clearCollectionCache('blog');

// Clear all entry caches (Redis only with tags)
$repository->clearAllCache();

// Clear all URI caches (useful after bulk URI updates)
$repository->clearUriCache();

// Or use the service provider helper
$provider = app(\Hastinbe\CachedEloquentGlobals\ServiceProvider::class);
$provider->clearCollectionCache('blog');
$provider->clearUriCache();
```

## Development & Testing

### Running Tests

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run tests with coverage report
composer test-coverage
```

The test suite includes:
- Unit tests for all repository methods
- Integration tests for caching behavior
- Tests for configuration handling
- Cache invalidation tests
- Redis tag detection tests

See `tests/README.md` for more details.

## Performance Considerations

### Cache Driver Recommendations

**Best**: Redis with tag support
- Enables efficient bulk invalidation
- Great for multi-server setups
- Ideal for high-traffic sites

**Good**: Memcached
- Also supports tags
- Fast in-memory caching

**Acceptable**: Database/File cache
- Falls back to key-based invalidation
- Still provides performance benefits
- Good for single-server setups

### Recommended TTL Settings

| Content Type | Recommended TTL | Use Case |
|-------------|-----------------|----------|
| Global Variables | 24 hours (default) | Rarely change |
| Popular Collections | 5-15 minutes | Balance freshness/performance |
| Frequently Updated | 1-3 minutes | News, events, live data |
| Excluded Collections | No cache | Real-time requirements |

## License

MIT

