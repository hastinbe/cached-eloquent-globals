<?php

namespace Hastinbe\CachedEloquentGlobals\Tests;

use Hastinbe\CachedEloquentGlobals\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}

