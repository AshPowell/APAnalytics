<?php

namespace AshPowell\APAnalytics;

use AshPowell\APAnalytics\Events\AnalyticTracked;
use AshPowell\APAnalytics\Listeners\AnalyticTrackedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class APAnalyticsEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        AnalyticTracked::class => [
            AnalyticTrackedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        //
    }
}
