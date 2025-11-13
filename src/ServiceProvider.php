<?php

namespace Hastinbe\CachedEloquentGlobals;

use Hastinbe\CachedEloquentGlobals\Repositories\CachedGlobalVariablesRepository;
use Hastinbe\CachedEloquentGlobals\Repositories\CachedEntryRepository;
use Statamic\Contracts\Globals\GlobalVariablesRepository as GlobalVariablesRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
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
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function bootAddon(): void
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/cached-eloquent-globals.php' => config_path('cached-eloquent-globals.php'),
        ], 'cached-eloquent-globals-config');

        $this->publishes([
            __DIR__.'/../config/cached-eloquent-entries.php' => config_path('cached-eloquent-entries.php'),
        ], 'cached-eloquent-entries-config');

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
}

