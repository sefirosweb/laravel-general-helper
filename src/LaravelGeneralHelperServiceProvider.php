<?php

namespace Sefirosweb\LaravelGeneralHelper;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelGeneralHelperServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/config.php', 'laravel-general-helper');
        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('laravel-general-helper'),
        ], 'config');
    }


    public function register()
    {
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('laravel-general-helper.prefix'),
            'middleware' => config('laravel-general-helper.middleware'),
        ];
    }
}
