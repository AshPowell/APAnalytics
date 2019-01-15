<?php

namespace AshPowell\APAnalytics;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use AshPowell\APAnalytics\Events\AnalyticTracked;
use AshPowell\APAnalytics\Listeners\AnalyticTrackedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        AnalyticTracked::class => [
            AnalyticTrackedListener::class,
        ]
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
