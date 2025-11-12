<?php

namespace Hastinbe\CachedEloquentGlobals;

use Hastinbe\CachedEloquentGlobals\Repositories\CachedGlobalVariablesRepository;
use Statamic\Contracts\Globals\GlobalVariablesRepository;
use Statamic\Events\GlobalSetSaved;
use Statamic\Events\GlobalVariablesSaved;
use Statamic\Providers\AddonServiceProvider;
use Illuminate\Support\Facades\Event;

class ServiceProvider extends AddonServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            GlobalVariablesRepository::class,
            CachedGlobalVariablesRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function bootAddon(): void
    {
        $this->publishes([
            __DIR__.'/../config/cached-eloquent-globals.php' => config_path('cached-eloquent-globals.php'),
        ], 'cached-eloquent-globals-config');

        $this->registerEventListeners();
    }

    /**
     * Register event listeners for automatic cache invalidation
     *
     * @return void
     */
    protected function registerEventListeners(): void
    {
        Event::listen(GlobalSetSaved::class, function ($event) {
            $this->clearGlobalCache($event->globals->handle());
        });

        Event::listen(GlobalVariablesSaved::class, function ($event) {
            $this->clearGlobalCache($event->variables->globalSet()->handle());
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
        $repository = app(GlobalVariablesRepository::class);
        if (method_exists($repository, 'clearCache')) {
            /** @var CachedGlobalVariablesRepository $repository */
            $repository->clearCache($handle);
        }
    }
}

