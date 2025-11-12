<?php

namespace Hastinbe\CachedEloquentGlobals\Tests\Unit;

use Hastinbe\CachedEloquentGlobals\Repositories\CachedGlobalVariablesRepository;
use Hastinbe\CachedEloquentGlobals\ServiceProvider;
use Hastinbe\CachedEloquentGlobals\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use Statamic\Contracts\Globals\GlobalVariablesRepository;
use Statamic\Events\GlobalSetSaved;
use Statamic\Events\GlobalVariablesSaved;

class ServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testItRegistersCachedRepositoryAsSingleton()
    {
        $repository = $this->app->make(GlobalVariablesRepository::class);

        $this->assertInstanceOf(
            CachedGlobalVariablesRepository::class,
            $repository,
            'Repository should be instance of CachedGlobalVariablesRepository'
        );

        // Verify it's a singleton
        $repository2 = $this->app->make(GlobalVariablesRepository::class);
        $this->assertSame(
            $repository,
            $repository2,
            'Repository should be registered as a singleton'
        );

        // Mark that we've performed assertions for PHPUnit
        $this->addToAssertionCount(2);
    }

    public function testItRegistersEventListeners()
    {
        // Verify listeners are registered for GlobalSetSaved
        $this->assertTrue(
            Event::hasListeners(GlobalSetSaved::class),
            'GlobalSetSaved event should have listeners'
        );

        // Verify listeners are registered for GlobalVariablesSaved
        $this->assertTrue(
            Event::hasListeners(GlobalVariablesSaved::class),
            'GlobalVariablesSaved event should have listeners'
        );

        // Mark that we've performed assertions for PHPUnit
        $this->addToAssertionCount(2);
    }

    public function testItClearsCacheWhenGlobalSetSavedEventFires()
    {
        $handle = 'test_handle';
        $cacheKey = 'cached_global_variables:' . $handle;

        // Put something in cache
        Cache::put($cacheKey, 'test_value', 3600);
        $this->assertTrue(Cache::has($cacheKey), 'Cache should exist before event');

        // Mock the globals object
        $globals = Mockery::mock();
        $globals->shouldReceive('handle')->andReturn($handle);

        // Create and fire the event
        $event = new GlobalSetSaved($globals);
        Event::dispatch($event);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey), 'Cache should be cleared after event');

        // Mark that we've performed assertions for PHPUnit
        $this->addToAssertionCount(2);
    }

    public function testItClearsCacheWhenGlobalVariablesSavedEventFires()
    {
        $handle = 'test_handle';
        $cacheKey = 'cached_global_variables:' . $handle;

        // Put something in cache
        Cache::put($cacheKey, 'test_value', 3600);
        $this->assertTrue(Cache::has($cacheKey), 'Cache should exist before event');

        // Mock the global set and variables
        $globalSet = Mockery::mock();
        $globalSet->shouldReceive('handle')->andReturn($handle);

        $variables = Mockery::mock();
        $variables->shouldReceive('globalSet')->andReturn($globalSet);

        // Create and fire the event
        $event = new GlobalVariablesSaved($variables);
        Event::dispatch($event);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey), 'Cache should be cleared after event');

        // Mark that we've performed assertions for PHPUnit
        $this->addToAssertionCount(2);
    }

    public function testItOnlyClearsCacheForSpecificHandleOnEvents()
    {
        $handle1 = 'handle1';
        $handle2 = 'handle2';
        $cacheKey1 = 'cached_global_variables:' . $handle1;
        $cacheKey2 = 'cached_global_variables:' . $handle2;

        // Put data in cache for both handles
        Cache::put($cacheKey1, 'test_value_1', 3600);
        Cache::put($cacheKey2, 'test_value_2', 3600);
        $this->assertTrue(Cache::has($cacheKey1) && Cache::has($cacheKey2), 'Both caches should exist initially');

        // Mock the globals object for handle1
        $globals = Mockery::mock();
        $globals->shouldReceive('handle')->andReturn($handle1);

        // Fire event for handle1
        $event = new GlobalSetSaved($globals);
        Event::dispatch($event);

        // Only handle1 cache should be cleared
        $handle1Cleared = !Cache::has($cacheKey1);
        $handle2Exists = Cache::has($cacheKey2);

        $this->assertTrue($handle1Cleared, 'Handle 1 cache should be cleared');
        $this->assertTrue($handle2Exists, 'Handle 2 cache should still exist');
    }

    public function testItPublishesConfigFile()
    {
        $published = ServiceProvider::pathsToPublish(ServiceProvider::class, 'cached-eloquent-globals-config');

        $this->assertNotEmpty($published);

        // Check that a config file is being published
        $configPath = array_keys($published)[0] ?? null;
        $this->assertNotNull($configPath);
        $this->assertStringEndsWith('config/cached-eloquent-globals.php', $configPath);
    }

    public function testClearGlobalCacheMethodIsSafeWhenRepositoryLacksClearCache()
    {
        // Create a mock repository without clearCache method
        $mockRepository = Mockery::mock(GlobalVariablesRepository::class);

        $this->app->instance(
            GlobalVariablesRepository::class,
            $mockRepository
        );

        $provider = $this->app->getProvider(ServiceProvider::class);
        $this->assertNotNull($provider, 'ServiceProvider should be registered');

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('clearGlobalCache');
        $method->setAccessible(true);

        // Should not throw exception even though clearCache method doesn't exist
        try {
            $method->invoke($provider, 'test_handle');
            $exceptionThrown = false;
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertFalse($exceptionThrown, 'Should not throw exception when clearCache method is missing');

        // Mark that we've performed assertions for PHPUnit
        $this->addToAssertionCount(2);
    }
}

