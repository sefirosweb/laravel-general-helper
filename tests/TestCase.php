<?php

namespace Sefirosweb\LaravelGeneralHelper\Tests;

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Sefirosweb\LaravelGeneralHelper\LaravelGeneralHelperServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DomPdfServiceProvider::class,
            LaravelGeneralHelperServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
