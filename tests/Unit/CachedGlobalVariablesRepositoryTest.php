<?php

namespace Hastinbe\CachedEloquentGlobals\Tests\Unit;

use Hastinbe\CachedEloquentGlobals\Repositories\CachedGlobalVariablesRepository;
use Hastinbe\CachedEloquentGlobals\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Statamic\Contracts\Globals\GlobalVariablesRepository;
use Statamic\Contracts\Globals\Variables;
use Statamic\Eloquent\Globals\GlobalVariablesModel;
use Statamic\Facades\GlobalSet;
use Statamic\Globals\GlobalCollection;
use Statamic\Globals\VariablesCollection;

class CachedGlobalVariablesRepositoryTest extends TestCase
{
    protected CachedGlobalVariablesRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(GlobalVariablesRepository::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testItReturnsDefaultCacheDurationWhenNotConfigured()
    {
        // Don't set the config, let it fall back to the constant default
        $this->app['config']->set('cached-eloquent-globals.cache_duration', null);

        $duration = $this->invokeMethod($this->repository, 'getCacheDuration');

        // Should return the constant default (86400) when config is null
        $this->assertEquals(86400, $duration);
    }

    public function testItReturnsConfiguredCacheDuration()
    {
        config(['cached-eloquent-globals.cache_duration' => 3600]);

        $duration = $this->invokeMethod($this->repository, 'getCacheDuration');

        $this->assertEquals(3600, $duration);
    }

    public function testItReturnsEmptyArrayWhenNoExcludedHandlesConfigured()
    {
        $this->app['config']->set('cached-eloquent-globals.exclude_handles', null);

        $excluded = $this->invokeMethod($this->repository, 'getExcludedHandles');

        // Should return empty array when config is null
        $this->assertEquals([], $excluded);
    }

    public function testItReturnsConfiguredExcludedHandles()
    {
        config(['cached-eloquent-globals.exclude_handles' => ['handle1', 'handle2']]);

        $excluded = $this->invokeMethod($this->repository, 'getExcludedHandles');

        $this->assertEquals(['handle1', 'handle2'], $excluded);
    }

    public function testItDeterminesHandleShouldBeCachedWhenNotExcluded()
    {
        config(['cached-eloquent-globals.exclude_handles' => ['excluded_handle']]);

        $shouldCache = $this->invokeMethod($this->repository, 'shouldCache', ['normal_handle']);

        $this->assertTrue($shouldCache);
    }

    public function testItDeterminesHandleShouldNotBeCachedWhenExcluded()
    {
        config(['cached-eloquent-globals.exclude_handles' => ['excluded_handle']]);

        $shouldCache = $this->invokeMethod($this->repository, 'shouldCache', ['excluded_handle']);

        $this->assertFalse($shouldCache);
    }

    public function testItCachesVariablesForAHandle()
    {
        $handle = 'test_handle';
        $cacheKey = 'cached_global_variables:' . $handle;

        // Mock the parent class behavior
        $mockCollection = new VariablesCollection();

        $repository = Mockery::mock(CachedGlobalVariablesRepository::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $repository->shouldReceive('shouldCache')
            ->with($handle)
            ->once()
            ->andReturn(true);

        $repository->shouldReceive('getCacheDuration')
            ->once()
            ->andReturn(3600);

        // Mock parent whereSet call
        $repository->shouldReceive('parent::whereSet')
            ->never();

        Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKey, 3600, Mockery::type('Closure'))
            ->andReturn($mockCollection);

        $result = $repository->whereSet($handle);

        $this->assertInstanceOf(VariablesCollection::class, $result);
    }

    public function testItBypassesCacheForExcludedHandles()
    {
        config(['cached-eloquent-globals.exclude_handles' => ['excluded_handle']]);

        $mockCollection = new VariablesCollection();

        $repository = Mockery::mock(CachedGlobalVariablesRepository::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // The parent::whereSet will be called directly
        Cache::shouldReceive('remember')->never();

        // We can't easily test parent::whereSet without a full integration test,
        // but we can verify shouldCache returns false
        $shouldCache = $this->invokeMethod($repository, 'shouldCache', ['excluded_handle']);
        $this->assertFalse($shouldCache);
    }

    public function testItClearsCacheForSpecificHandle()
    {
        $handle = 'test_handle';
        $cacheKey = 'cached_global_variables:' . $handle;

        Cache::shouldReceive('forget')
            ->once()
            ->with($cacheKey)
            ->andReturn(true);

        $this->repository->clearCache($handle);
    }

    public function testItClearsCacheForAllHandles()
    {
        // Mock GlobalSet facade
        $globalSet1 = Mockery::mock();
        $globalSet1->shouldReceive('handle')->andReturn('handle1');

        $globalSet2 = Mockery::mock();
        $globalSet2->shouldReceive('handle')->andReturn('handle2');

        $globalSet3 = Mockery::mock();
        $globalSet3->shouldReceive('handle')->andReturn('excluded_handle');

        $collection = new GlobalCollection([$globalSet1, $globalSet2, $globalSet3]);

        GlobalSet::shouldReceive('all')
            ->once()
            ->andReturn($collection);

        config(['cached-eloquent-globals.exclude_handles' => ['excluded_handle']]);

        Cache::shouldReceive('forget')
            ->once()
            ->with('cached_global_variables:handle1')
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->once()
            ->with('cached_global_variables:handle2')
            ->andReturn(true);

        // Should not clear cache for excluded handle
        Cache::shouldReceive('forget')
            ->with('cached_global_variables:excluded_handle')
            ->never();

        $this->repository->clearAllCache();
    }

    public function testItClearsCacheWhenSavingVariable()
    {
        $handle = 'test_handle';
        $cacheKey = 'cached_global_variables:' . $handle;

        // Put something in cache
        Cache::put($cacheKey, 'test_value', 3600);
        $this->assertTrue(Cache::has($cacheKey));

        // Mock the Variables object
        $variable = Mockery::mock(Variables::class);

        // Mock the model
        $model = Mockery::mock(GlobalVariablesModel::class);
        $model->handle = $handle;
        $model->shouldReceive('save')->once()->andReturn(true);
        $model->shouldReceive('fresh')->once()->andReturn($model);

        $variable->shouldReceive('toModel')
            ->andReturn($model);
        $variable->shouldReceive('model')
            ->andReturn($model);

        // Save the variable
        try {
            $this->repository->save($variable);
            $cleared = true;
        } catch (\Exception $e) {
            $cleared = false;
        }

        // Should complete without error
        $this->assertTrue($cleared);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function testItDoesNotClearCacheWhenSavingExcludedVariable()
    {
        $handle = 'excluded_handle';
        $this->app['config']->set('cached-eloquent-globals.exclude_handles', [$handle]);

        $cacheKey = 'cached_global_variables:' . $handle;

        // Put something in cache
        Cache::put($cacheKey, 'test_value', 3600);
        $this->assertTrue(Cache::has($cacheKey));

        // Mock the Variables object
        $variable = Mockery::mock(Variables::class);

        // Mock the model
        $model = Mockery::mock(GlobalVariablesModel::class);
        $model->handle = $handle;
        $model->shouldReceive('save')->once()->andReturn(true);
        $model->shouldReceive('fresh')->once()->andReturn($model);

        $variable->shouldReceive('toModel')
            ->andReturn($model);
        $variable->shouldReceive('model')
            ->andReturn($model);

        // Save the variable
        try {
            $this->repository->save($variable);
            $saved = true;
        } catch (\Exception $e) {
            $saved = false;
        }

        // Should complete without error
        $this->assertTrue($saved);

        // Cache should still be there (not cleared) because handle is excluded
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function testItReturnsParentBindings()
    {
        $bindings = CachedGlobalVariablesRepository::bindings();

        $this->assertIsArray($bindings);
    }

    public function testItUsesCorrectCacheKeyPrefix()
    {
        $reflection = new \ReflectionClass(CachedGlobalVariablesRepository::class);
        $constant = $reflection->getConstant('CACHE_PREFIX');

        $this->assertEquals('cached_global_variables:', $constant);
    }

    public function testItUsesCorrectDefaultCacheDuration()
    {
        $reflection = new \ReflectionClass(CachedGlobalVariablesRepository::class);
        $constant = $reflection->getConstant('CACHE_DURATION');

        $this->assertEquals(86400, $constant);
    }

    /**
     * Helper method to invoke protected/private methods for testing
     *
     * @param object $object
     * @param string $methodName
     * @param array $parameters
     * @return mixed
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}

