<?php

namespace Sefirosweb\LaravelGeneralHelper;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Sefirosweb\LaravelGeneralHelper\Commands\RemoveTempFiles;

class LaravelGeneralHelperServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (File::exists(__DIR__ . '/Helpers/GeneralHelperFunctions.php')) {
            require __DIR__ . '/Helpers/GeneralHelperFunctions.php';
        }

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->mergeConfigFrom(__DIR__ . '/config/config.php', 'laravel-general-helper');
        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('laravel-general-helper.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RemoveTempFiles::class,
            ]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            // $schedule->command('purge:temp')->cron('* * * * *')
            //     ->runInBackground();
        });
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
