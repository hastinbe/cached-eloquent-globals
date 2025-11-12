<?php

namespace Hastinbe\CachedEloquentGlobals\Tests\Unit;

use Hastinbe\CachedEloquentGlobals\Repositories\CachedGlobalVariablesRepository;
use Hastinbe\CachedEloquentGlobals\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Globals\GlobalVariablesRepository;

/**
 * Example integration test showing how to test with actual cache operations
 *
 * This test demonstrates a more integration-focused approach where we test
 * the actual caching behavior without extensive mocking.
 */
class ExampleIntegrationTest extends TestCase
{
    protected CachedGlobalVariablesRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(GlobalVariablesRepository::class);
        Cache::flush();
    }

    public function testItCanClearCacheUsingClearCacheMethod()
    {
        $handle = 'test_handle';
        $cacheKey = 'cached_global_variables:' . $handle;

        // Put something in cache
        Cache::put($cacheKey, 'test_value', 3600);

        // Verify it's in cache
        $this->assertTrue(Cache::has($cacheKey));

        // Clear it using our method
        $this->repository->clearCache($handle);

        // Verify it's gone
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function testItRespectsCacheDurationConfiguration()
    {
        // Set custom cache duration
        config(['cached-eloquent-globals.cache_duration' => 7200]);

        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getCacheDuration');
        $method->setAccessible(true);

        $duration = $method->invoke($this->repository);

        $this->assertEquals(7200, $duration);
    }

    public function testItCorrectlyIdentifiesExcludedHandles()
    {
        config(['cached-eloquent-globals.exclude_handles' => ['nocache1', 'nocache2']]);

        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('shouldCache');
        $method->setAccessible(true);

        // Should not cache excluded handles
        $this->assertFalse($method->invoke($this->repository, 'nocache1'));
        $this->assertFalse($method->invoke($this->repository, 'nocache2'));

        // Should cache other handles
        $this->assertTrue($method->invoke($this->repository, 'normal_handle'));
    }

    public function testItUsesCorrectCacheKeyFormat()
    {
        $handle = 'my_global_set';
        $expectedKey = 'cached_global_variables:my_global_set';

        // Put something in cache with the expected key
        Cache::put($expectedKey, 'test_value', 3600);

        // Clear using our method with just the handle
        $this->repository->clearCache($handle);

        // Should have cleared the cache with the right key
        $this->assertFalse(Cache::has($expectedKey));
    }
}

