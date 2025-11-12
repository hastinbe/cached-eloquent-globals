# Tests

This directory contains the test suite for the Cached Eloquent Globals addon.

## Setup

Before running tests, make sure you have installed the development dependencies:

```bash
composer install
```

## Running Tests

Run all tests:

```bash
composer test
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit
```

### With Coverage Report

Generate an HTML coverage report:

```bash
composer test-coverage
```

The coverage report will be generated in the `coverage/` directory.

## Test Structure

The test suite uses Statamic's `AddonTestCase` which provides all the necessary setup for testing Statamic addons, including:
- Automatic service provider registration
- Database migrations
- Cache configuration
- Test helpers

### Unit Tests

- **CachedGlobalVariablesRepositoryTest.php** - Tests for the main repository class
  - Configuration methods (cache duration, excluded handles)
  - Caching behavior for global variables
  - Cache invalidation on save
  - Bulk cache clearing
  - Exclusion logic

- **ServiceProviderTest.php** - Tests for service provider functionality
  - Repository binding
  - Event listener registration
  - Automatic cache invalidation on events

## Test Coverage

The test suite covers:

1. **Configuration**:
   - Cache duration settings
   - Excluded handles configuration
   - Default values

2. **Caching Logic**:
   - Variables are cached correctly
   - Cache keys are properly formatted
   - Excluded handles bypass cache
   - Cache TTL is respected

3. **Cache Invalidation**:
   - Single handle cache clearing
   - Bulk cache clearing for all handles
   - Automatic invalidation on save
   - Excluded handles are not cleared

4. **Edge Cases**:
   - Null configuration values
   - Empty arrays
   - Missing models

## Writing New Tests

When adding new functionality, please add corresponding tests following the existing patterns:

1. Use descriptive test method names with `test` prefix in camelCase (e.g., `testItDoesSomething()`)
2. Follow the Arrange-Act-Assert pattern
3. Mock external dependencies (Cache, GlobalSet facades)
4. Test both happy paths and edge cases
5. Keep tests focused on a single behavior

### Test Naming Convention

All test methods must start with `test` in camelCase to be recognized by PHPUnit:

```php
public function testItCachesVariablesCorrectly()
{
    // Arrange
    $handle = 'test_handle';

    // Act
    $result = $this->repository->whereSet($handle);

    // Assert
    $this->assertNotNull($result);
}
```

## Mocking Strategy

The tests use Mockery for mocking dependencies:

- **Cache Facade**: Mocked to verify caching operations without actual cache storage
- **GlobalSet Facade**: Mocked to provide test data without database
- **Variables Objects**: Mocked to isolate repository logic from Statamic internals
- **Parent Methods**: Partially mocked to test cache wrapper without parent implementation
- **Event System**: Uses Statamic's event system to test automatic cache invalidation

## Test Base Class

All tests extend `Hastinbe\CachedEloquentGlobals\Tests\TestCase` which extends Statamic's `AddonTestCase`. This provides:
- Automatic addon service provider registration
- Statamic testing utilities and helpers
- Proper application bootstrapping for addon testing
- Test database and cache setup

## Continuous Integration

These tests are designed to run in CI/CD pipelines with minimal setup. The `phpunit.xml` configuration uses array cache driver and SQLite in-memory database for fast, isolated test execution.

Statamic's testing infrastructure handles most of the heavy lifting, so you just need to install dependencies and run the tests.

