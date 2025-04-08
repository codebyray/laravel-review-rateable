<?php

namespace Codebyray\ReviewRateable\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Codebyray\ReviewRateable\ReviewRateableServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ReviewRateableServiceProvider::class,
        ];
    }
}
