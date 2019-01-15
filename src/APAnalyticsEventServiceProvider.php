<?php

namespace AshPowell\APAnalytics;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use AshPowell\APAnalytics\Events\TrackAnalytic;
use AshPowell\APAnalytics\Listeners\TrackAnalyticListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        TrackAnalytic::class => [
            TrackAnalyticListener::class,
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
