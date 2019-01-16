<?php

namespace AshPowell\APAnalytics;

use Illuminate\Support\ServiceProvider;

class APAnalyticsServiceProvider extends ServiceProvider
{
    const CONFIG_PATH = __DIR__.'/../config/apanalytics.php';

    public function boot()
    {
        $this->publishes([self::CONFIG_PATH => config_path('apanalytics.php')], 'config');

        $this->publishes([__DIR__.'/resources/js/components' => resource_path('js/components')], 'views');

        $this->loadRoutesFrom(__DIR__.'/Routes.php');
    }

    public function register()
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'apanalytics');

        //$this->app->register(APAnalyticsEventServiceProvider::class);

        $this->app->singleton('apanalytics', function () {
            return new APAnalytics();
        });
    }
}
