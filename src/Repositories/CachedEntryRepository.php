<?php

namespace Hastinbe\CachedEloquentGlobals\Repositories;

use Illuminate\Support\Facades\Cache;
use Statamic\Eloquent\Entries\EntryRepository as BaseRepository;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Entries\EntryCollection;

class CachedEntryRepository extends BaseRepository
{
    /**
     * Cache duration in seconds (5 minutes default)
     */
    const CACHE_DURATION = 300;

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'cached_entries:';

    /**
     * Check if the current cache driver supports tags
     *
     * @var bool|null
     */
    protected static ?bool $supportsTagging = null;

    /**
     * Get cache duration from config
     */
    protected function getCacheDuration(): int
    {
        return config('cached-eloquent.entries.cache_duration') ?? self::CACHE_DURATION;
    }

    /**
     * Determine if caching should be used
     */
    protected function shouldCache(?string $collection = null): bool
    {
        if (!config('cached-eloquent.entries.enabled', true)) {
            return false;
        }

        // Check if collection is excluded from caching
        if ($collection && $this->isCollectionExcluded($collection)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a collection should be excluded from caching
     */
    protected function isCollectionExcluded(string $collection): bool
    {
        $excluded = config('cached-eloquent.entries.exclude_collections', []);
        return in_array($collection, $excluded, true);
    }

    /**
     * Check if cache driver supports tagging (Redis, Memcached)
     */
    protected function supportsTagging(): bool
    {
        if (static::$supportsTagging === null) {
            try {
                static::$supportsTagging = method_exists(Cache::store(), 'tags');
            } catch (\Exception $e) {
                static::$supportsTagging = false;
            }
        }

        return static::$supportsTagging;
    }

    /**
     * Get tags for a collection
     */
    protected function getCollectionTags(string $collection): array
    {
        return [
            'entries',
            "collection:{$collection}",
        ];
    }

    /**
     * Get tags for an entry
     */
    protected function getEntryTags(EntryContract $entry): array
    {
        return [
            'entries',
            "collection:{$entry->collectionHandle()}",
            "entry:{$entry->id()}",
        ];
    }

    /**
     * Cache key for collection queries
     */
    protected function collectionCacheKey(string $collection): string
    {
        return self::CACHE_PREFIX . "collection:{$collection}:published";
    }

    /**
     * Find with caching and Blink
     */
    public function find($id): ?EntryContract
    {
        // Blink cache is still useful for request-level deduplication
        return parent::find($id);
    }

    /**
     * Cache whereInId results with optional tagging
     * This is useful for navigation, listings, etc.
     */
    public function whereInId($ids): EntryCollection
    {
        if (!$this->shouldCache()) {
            return parent::whereInId($ids);
        }

        $cacheKey = self::CACHE_PREFIX . 'ids:' . md5(implode(',', (array)$ids));

        // Use tags if supported for easier invalidation
        if ($this->supportsTagging()) {
            return Cache::tags(['entries'])->remember(
                $cacheKey,
                $this->getCacheDuration(),
                fn() => parent::whereInId($ids)
            );
        }

        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($ids) {
            return parent::whereInId($ids);
        });
    }

    /**
     * Cache findByUri with tagging support
     * URIs can change so we use a shorter TTL and rely heavily on invalidation
     */
    public function findByUri(string $uri, ?string $site = null): ?EntryContract
    {
        if (!$this->shouldCache()) {
            return parent::findByUri($uri, $site);
        }

        $cacheKey = self::CACHE_PREFIX . 'uri:' . md5($uri . ($site ?? 'default'));

        // Use tags if supported for easier invalidation when entries change
        if ($this->supportsTagging()) {
            return Cache::tags(['entries', 'uris'])->remember(
                $cacheKey,
                $this->getCacheDuration(),
                fn() => parent::findByUri($uri, $site)
            );
        }

        // Without tags, still cache but rely on manual invalidation
        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($uri, $site) {
            return parent::findByUri($uri, $site);
        });
    }

    /**
     * Save and invalidate related caches
     */
    public function save($entry)
    {
        parent::save($entry);

        // Invalidate caches for this entry
        $this->invalidateEntry($entry);
    }

    /**
     * Delete and invalidate caches
     */
    public function delete($entry)
    {
        parent::delete($entry);

        // Invalidate caches for this entry
        $this->invalidateEntry($entry);
    }

    /**
     * Invalidate all caches related to an entry
     */
    protected function invalidateEntry(EntryContract $entry): void
    {
        $collection = $entry->collectionHandle();

        if ($this->supportsTagging()) {
            // Use tags for efficient invalidation
            Cache::tags($this->getEntryTags($entry))->flush();

            // Also clear URI caches when any entry changes
            // This is important because URIs can change and affect routing
            Cache::tags(['uris'])->flush();
        } else {
            // Manual cache key invalidation
            $this->invalidateCollectionCache($collection);
            $this->invalidateEntryCache($entry->id());
            $this->invalidateUriCache($entry->uri());
        }
    }

    /**
     * Clear cache for a specific URI (non-tagged fallback)
     */
    protected function invalidateUriCache(string $uri): void
    {
        // We need to clear for all possible sites since we don't know which one
        // This is a limitation of non-tagged caching
        $sites = \Statamic\Facades\Site::all()->map->handle();

        foreach ($sites as $site) {
            Cache::forget(self::CACHE_PREFIX . 'uri:' . md5($uri . $site));
        }

        // Also clear without site
        Cache::forget(self::CACHE_PREFIX . 'uri:' . md5($uri . 'default'));
    }

    /**
     * Clear cache for a specific collection (non-tagged fallback)
     */
    protected function invalidateCollectionCache(string $collection): void
    {
        // Clear specific collection cache keys
        Cache::forget($this->collectionCacheKey($collection));

        // You might have other collection-specific cache keys
        // Add them here as needed
    }

    /**
     * Clear cache for a specific entry (non-tagged fallback)
     */
    protected function invalidateEntryCache(string $id): void
    {
        Cache::forget(self::CACHE_PREFIX . "entry:{$id}");
    }

    /**
     * Clear all entry caches
     * Useful for manual cache clearing or deployments
     */
    public function clearAllCache(): void
    {
        if ($this->supportsTagging()) {
            Cache::tags(['entries'])->flush();
            Cache::tags(['uris'])->flush();
        } else {
            // Without tags, we can't easily clear all entry caches
            // Consider implementing a list of cache keys to track
            // For now, just flush the entire cache if needed
            // Cache::flush(); // Use with caution!
        }
    }

    /**
     * Clear cache for a specific collection
     */
    public function clearCollectionCache(string $collection): void
    {
        if ($this->supportsTagging()) {
            Cache::tags(["collection:{$collection}"])->flush();
            // Also clear URI caches as URIs may have changed
            Cache::tags(['uris'])->flush();
        } else {
            $this->invalidateCollectionCache($collection);
        }
    }

    /**
     * Clear all URI caches
     * Useful when doing bulk URI updates or route changes
     */
    public function clearUriCache(): void
    {
        if ($this->supportsTagging()) {
            Cache::tags(['uris'])->flush();
        } else {
            // Without tags, we'd need to track all URI cache keys
            // This is impractical, so consider using Cache::flush() carefully
            // or upgrade to Redis/Memcached for tag support
        }
    }

    /**
     * Update URIs and clear related caches
     */
    public function updateUris($collection, $ids = null)
    {
        parent::updateUris($collection, $ids);

        // URIs have changed, clear the URI cache
        if ($this->supportsTagging()) {
            Cache::tags(['uris'])->flush();
        }
        // For non-tagged caches, individual URIs are cleared in save()
    }

    /**
     * Bindings for Statamic
     */
    // public static function bindings(): array
    // {
    //     return parent::bindings();
    // }
}
