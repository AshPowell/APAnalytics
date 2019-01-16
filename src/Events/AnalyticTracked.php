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

    public $basename;
    public $item;
    public $itemId;

    /**
     * Create a new event instance.
     *
     * @return void
     * @param  mixed $item
     * @param  mixed $basename
     */
    public function __construct($basename, $item)
    {
        $this->basename = $basename;
        $this->item     = $item;
        $this->itemId   = array_get($this->item, "{$this->basename}.id");
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('analytics.'.$this->basename.'.'.$this->itemId);
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
            'itemType'   => $this->basename,
            'itemId'     => $this->itemId,
            'created_at' => $created_at->toDateTime()->getTimestamp()
        ];
    }
}
