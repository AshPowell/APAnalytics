<?php

namespace AshPowell\APAnalytics\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TrackAnalytic
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mongodb_connection;
    public $collection;
    public $items;
    public $userId;
    public $params;

    /**
     * Create a new event instance.
     *
     * @return void
     * @param  mixed $collection
     * @param  mixed $items
     * @param  mixed $userId
     * @param  mixed $params
     */
    public function __construct($collection, $items, $userId, $params)
    {
        $this->mongodb_connection = config('apanalytics.db_connection', 'mongodb');
        $this->collection         = $collection;
        $this->items              = $items;
        $this->userId             = $userId;
        $this->params             = $params;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        //$basename = strtolower(class_basename($this->item));
        //return new PresenceChannel('analytics.'.$basename.'.'.$this->item->id);
    }
}
