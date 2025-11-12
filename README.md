# Cached Eloquent Globals

A Statamic addon that provides automatic caching for Eloquent-based global variables to improve performance.

## Features

- **Automatic Caching**: All global variables are cached by default
- **Smart Invalidation**: Cache is automatically cleared when globals are saved
- **Configurable**: Exclude specific handles or adjust cache duration
- **Zero Configuration**: Works out of the box with sensible defaults
- **Event-Driven**: Listens to Statamic events for automatic cache clearing

> **⚠️ Important**: This addon depends on the `statamic/eloquent-driver` package. It only works when your `global_set_variables` driver is set to `eloquent` in the `config/statamic/eloquent-driver.php` configuration file.

## Installation

```bash
composer require hastinbe/cached-eloquent-globals
```

## Configuration

Publish the config file (optional):

```bash
php artisan vendor:publish --tag=cached-eloquent-globals-config
```

### Environment Variables

```env
# Cache duration in seconds (default: 86400 = 24 hours)
CACHED_GLOBALS_DURATION=86400

# Comma-separated list of handles to exclude from caching
CACHED_GLOBALS_EXCLUDE=handle1,handle2
```

### Config File

```php
// config/cached-eloquent-globals.php

return [
    'cache_duration' => 86400, // 24 hours

    'exclude_handles' => [
        // 'some_handle',
    ],
];
```

## How It Works

1. **Caching**: When `whereSet()` is called, results are cached using Laravel's Cache facade
2. **Invalidation**: Cache is automatically cleared when:
   - A global set is saved (`GlobalSetSaved` event)
   - Global variables are saved (`GlobalVariablesSaved` event)
   - A variable is saved via the repository's `save()` method

## Manual Cache Clearing

```php
use Statamic\Facades\GlobalSet;

// Clear cache for a specific handle
$repository = app(\Statamic\Contracts\Globals\GlobalVariablesRepository::class);
$repository->clearCache('advertising');

// Clear cache for all cached handles
$repository->clearAllCache();
```

## License

MIT

