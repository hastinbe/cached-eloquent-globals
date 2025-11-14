<?php

namespace Hastinbe\CachedEloquentGlobals;

use Hastinbe\CachedEloquentGlobals\Repositories\CachedGlobalVariablesRepository;
use Hastinbe\CachedEloquentGlobals\Repositories\CachedEntryRepository;
use Hastinbe\CachedEloquentGlobals\Repositories\CachedFieldsetRepository;
use Statamic\Contracts\Globals\GlobalVariablesRepository as GlobalVariablesRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Fields\FieldsetRepository as FieldsetRepositoryContract;
use Statamic\Events\GlobalSetSaved;
use Statamic\Events\GlobalVariablesSaved;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntryDeleted;
use Statamic\Providers\AddonServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;

class ServiceProvider extends AddonServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge default config so it's available immediately
        $this->mergeConfigFrom(
            __DIR__.'/../config/cached-eloquent.php',
            'cached-eloquent'
        );

        $this->app->booted(function () {
            // Don't use Statamic::repository() it conflicts with self::bindings() of the inherited class
            Facade::clearResolvedInstance(GlobalVariablesRepositoryContract::class);
            $this->app->singleton(
                GlobalVariablesRepositoryContract::class,
                CachedGlobalVariablesRepository::class
            );

            Facade::clearResolvedInstance(EntryRepositoryContract::class);
            $this->app->singleton(
                EntryRepositoryContract::class,
                CachedEntryRepository::class
            );

            Facade::clearResolvedInstance(FieldsetRepositoryContract::class);
            $this->app->singleton(
                FieldsetRepositoryContract::class,
                CachedFieldsetRepository::class
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function bootAddon(): void
    {
        // Publish merged config file
        $this->publishes([
            __DIR__.'/../config/cached-eloquent.php' => config_path('cached-eloquent.php'),
        ], 'cached-eloquent-config');

        $this->registerEventListeners();
    }

    /**
     * Register event listeners for automatic cache invalidation
     *
     * @return void
     */
    protected function registerEventListeners(): void
    {
        // Global variables events
        Event::listen(GlobalSetSaved::class, function ($event) {
            $this->clearGlobalCache($event->globals->handle());
        });

        Event::listen(GlobalVariablesSaved::class, function ($event) {
            $this->clearGlobalCache($event->variables->globalSet()->handle());
        });

        // Entry events - cache is handled by repository save/delete methods
        // But we can also listen here for additional logging or processing
        Event::listen(EntrySaved::class, function ($event) {
            // Cache invalidation is handled in CachedEntryRepository::save()
            // You can add additional logic here if needed (e.g., logging, webhooks)
        });

        Event::listen(EntryDeleted::class, function ($event) {
            // Cache invalidation is handled in CachedEntryRepository::delete()
            // You can add additional logic here if needed
        });
    }

    /**
     * Clear cache for a global set handle
     *
     * @param string $handle
     * @return void
     */
    protected function clearGlobalCache(string $handle): void
    {
        $repository = app(GlobalVariablesRepositoryContract::class);
        if (method_exists($repository, 'clearCache')) {
            /** @var CachedGlobalVariablesRepository $repository */
            $repository->clearCache($handle);
        }
    }

    /**
     * Clear cache for a specific collection (manual helper)
     *
     * @param string $collection
     * @return void
     */
    public function clearCollectionCache(string $collection): void
    {
        $repository = app(EntryRepositoryContract::class);
        if (method_exists($repository, 'clearCollectionCache')) {
            /** @var CachedEntryRepository $repository */
            $repository->clearCollectionCache($collection);
        }
    }

    /**
     * Clear all entry caches (manual helper)
     *
     * @return void
     */
    public function clearAllEntryCache(): void
    {
        $repository = app(EntryRepositoryContract::class);
        if (method_exists($repository, 'clearAllCache')) {
            /** @var CachedEntryRepository $repository */
            $repository->clearAllCache();
        }
    }

    /**
     * Clear all URI caches (manual helper)
     * Useful after bulk URI updates or route changes
     *
     * @return void
     */
    public function clearUriCache(): void
    {
        $repository = app(EntryRepositoryContract::class);
        if (method_exists($repository, 'clearUriCache')) {
            /** @var CachedEntryRepository $repository */
            $repository->clearUriCache();
        }
    }

    /**
     * Clear all fieldset caches (manual helper)
     *
     * @return void
     */
    public function clearAllFieldsetCache(): void
    {
        $repository = app(FieldsetRepositoryContract::class);
        if (method_exists($repository, 'clearAllCache')) {
            /** @var CachedFieldsetRepository $repository */
            $repository->clearAllCache();
        }
    }

    /**
     * Clear cache for a specific fieldset (manual helper)
     *
     * @param string $handle
     * @return void
     */
    public function clearFieldsetCache(string $handle): void
    {
        $repository = app(FieldsetRepositoryContract::class);
        if (method_exists($repository, 'clearCache')) {
            /** @var CachedFieldsetRepository $repository */
            $repository->clearCache($handle);
        }
    }
}

