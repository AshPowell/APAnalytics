<?php

namespace AshPowell\APAnalytics\Listeners;

use AshPowell\APAnalytics\Events\AnalyticTracked;
use Illuminate\Contracts\Queue\ShouldQueue;

class AnalyticTrackedListener implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'analytics';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AnalyticTracked  $event
     * @return void
     */
    public function handle(AnalyticTracked $event)
    {
        //
    }
}
