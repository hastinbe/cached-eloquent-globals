<?php

namespace Hastinbe\CachedEloquentGlobals\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Statamic\Eloquent\Fields\FieldsetRepository;
use Statamic\Fields\Fieldset;
use Statamic\Facades\Blink;

class CachedFieldsetRepository extends FieldsetRepository
{
    /**
     * Default cache duration (24 hours)
     * Fieldsets rarely change, so we can use a long TTL
     */
    const CACHE_DURATION = 86400;

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'cached_fieldsets:';

    /**
     * Static flag for tag support detection
     */
    protected static ?bool $supportsTagging = null;

    /**
     * Check if caching is enabled
     */
    protected function isCachingEnabled(): bool
    {
        return config('cached-eloquent.fieldsets.enabled', true);
    }

    /**
     * Get cache duration from config
     */
    protected function getCacheDuration(): int
    {
        return config('cached-eloquent.fieldsets.cache_duration') ?? self::CACHE_DURATION;
    }

    /**
     * Check if cache driver supports tagging
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
     * Get all fieldsets with caching
     *
     * Fieldsets are accessed frequently but change rarely,
     * making them ideal candidates for caching.
     */
    public function all(): Collection
    {
        if (!$this->isCachingEnabled()) {
            return parent::all();
        }

        $cacheKey = self::CACHE_PREFIX . 'all';

        // Use tags if supported for easier invalidation
        if ($this->supportsTagging()) {
            return Cache::tags(['fieldsets'])->remember(
                $cacheKey,
                $this->getCacheDuration(),
                fn() => parent::all()
            );
        }

        return Cache::remember($cacheKey, $this->getCacheDuration(), function () {
            return parent::all();
        });
    }

    /**
     * Find a fieldset by handle with caching
     *
     * Individual fieldset lookups are cached separately for faster access.
     */
    public function find($handle): ?Fieldset
    {
        if (!$this->isCachingEnabled()) {
            return parent::find($handle);
        }

        $cacheKey = self::CACHE_PREFIX . 'handle:' . md5($handle);

        // Use tags if supported for easier invalidation
        if ($this->supportsTagging()) {
            return Cache::tags(['fieldsets', "fieldset:{$handle}"])->remember(
                $cacheKey,
                $this->getCacheDuration(),
                fn() => parent::find($handle)
            );
        }

        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($handle) {
            return parent::find($handle);
        });
    }

    /**
     * Save and invalidate caches
     */
    public function save(Fieldset $fieldset)
    {
        parent::save($fieldset);
        $this->invalidateFieldset($fieldset);
    }

    /**
     * Delete and invalidate caches
     */
    public function delete(Fieldset $fieldset)
    {
        parent::delete($fieldset);
        $this->invalidateFieldset($fieldset);
    }

    /**
     * Invalidate cache for a specific fieldset
     */
    protected function invalidateFieldset(Fieldset $fieldset): void
    {
        $handle = $fieldset->handle();

        if ($this->supportsTagging()) {
            // Flush all fieldset caches (since 'all' depends on individual fieldsets)
            Cache::tags(['fieldsets'])->flush();
        } else {
            // Without tags, clear the main 'all' cache and the specific fieldset
            Cache::forget(self::CACHE_PREFIX . 'all');
            Cache::forget(self::CACHE_PREFIX . 'handle:' . md5($handle));
        }
    }

    /**
     * Clear all fieldset caches (manual helper)
     */
    public function clearAllCache(): void
    {
        if ($this->supportsTagging()) {
            Cache::tags(['fieldsets'])->flush();
        } else {
            Cache::forget(self::CACHE_PREFIX . 'all');
        }
    }

    /**
     * Clear cache for a specific fieldset handle (manual helper)
     */
    public function clearCache(string $handle): void
    {
        if ($this->supportsTagging()) {
            Cache::tags(["fieldset:{$handle}"])->flush();
        } else {
            Cache::forget(self::CACHE_PREFIX . 'handle:' . md5($handle));
            Cache::forget(self::CACHE_PREFIX . 'all');
        }
    }
}

