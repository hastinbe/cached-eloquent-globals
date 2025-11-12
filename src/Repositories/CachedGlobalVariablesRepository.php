<?php

namespace Hastinbe\CachedEloquentGlobals\Repositories;

use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Globals\Variables;
use Statamic\Globals\VariablesCollection;
use Statamic\Eloquent\Globals\GlobalVariablesRepository;

class CachedGlobalVariablesRepository extends GlobalVariablesRepository
{
    /**
     * Cache duration in seconds (24 hours)
     */
    const CACHE_DURATION = 86400;

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'cached_global_variables:';

    /**
     * Get list of handles that should be excluded from caching
     * Can be configured in config/cached-eloquent-globals.php
     *
     * @return array
     */
    protected function getExcludedHandles(): array
    {
        return config('cached-eloquent-globals.exclude_handles') ?? [];
    }

    /**
     * Get cache duration in seconds
     *
     * @return int
     */
    protected function getCacheDuration(): int
    {
        return config('cached-eloquent-globals.cache_duration') ?? self::CACHE_DURATION;
    }

    /**
     * Check if a handle should be cached
     *
     * @param string $handle
     * @return bool
     */
    protected function shouldCache(string $handle): bool
    {
        $excluded = $this->getExcludedHandles();
        return !in_array($handle, $excluded, true);
    }

    /**
     * Get variables for a specific global set handle with caching
     *
     * @param string $handle
     * @return VariablesCollection
     */
    public function whereSet($handle): VariablesCollection
    {
        if (!$this->shouldCache($handle)) {
            return parent::whereSet($handle);
        }

        $cacheKey = self::CACHE_PREFIX . $handle;

        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($handle) {
            return parent::whereSet($handle);
        });
    }

    /**
     * Clear cache for a specific handle
     *
     * @param string $handle
     * @return void
     */
    public function clearCache(string $handle): void
    {
        Cache::forget(self::CACHE_PREFIX . $handle);
    }

    /**
     * Clear cache for all cached handles
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        $allHandles = \Statamic\Facades\GlobalSet::all()->map->handle()->toArray();

        foreach ($allHandles as $handle) {
            if ($this->shouldCache($handle)) {
                $this->clearCache($handle);
            }
        }
    }

    /**
     * Override save to clear cache
     *
     * @param Variables|\Statamic\Eloquent\Globals\Variables $variable
     * @return void
     */
    public function save($variable)
    {
        parent::save($variable);

        /** @var \Statamic\Eloquent\Globals\Variables $variable */
        $model = $variable->toModel();
        if ($model && $this->shouldCache($model->handle)) {
            $this->clearCache($model->handle);
        }
    }

    /**
     * Bindings for Statamic
     *
     * @return array
     */
    public static function bindings(): array
    {
        return parent::bindings();
    }
}

