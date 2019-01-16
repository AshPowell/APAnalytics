<?php

namespace AshPowell\APAnalytics\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticTracked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $collection;
    public $basename;
    public $item;
    public $itemId;

    /**
     * Create a new event instance.
     *
     * @return void
     * @param  mixed $item
     * @param  mixed $basename
     * @param mixed $collection
     */
    public function __construct($collection, $basename, $item)
    {
        $this->collection = $collection;
        $this->basename   = $basename;
        $this->item       = $item;
        $this->itemId     = array_get($this->item, "{$this->basename}.id");
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel("analytics.{$this->collection}.{$this->basename}.{$this->itemId}");
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $created_at = array_get($this->item, 'created_at') ?? mongoTime();

        return [
            'collection' => $this->collection,
            'itemType'   => $this->basename,
            'itemId'     => $this->itemId,
            'created_at' => $created_at->toDateTime()->format('c'),
        ];
    }
}
