<?php

namespace AshPowell\APAnalytics;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const CONFIG_PATH = __DIR__ . '/../config/apanalytics.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('apanalytics.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/resources/js/components' => resource_path('js/components'),
        ]);

        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            self::CONFIG_PATH,
            'apanalytics'
        );

        $this->app->bind('apanalytics', function () {
            return new APAnalytics();
        });
    }
}
