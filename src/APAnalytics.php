<?php

namespace AshPowell\APAnalytics;

use AshPowell\APAnalytics\Jobs\TrackEvent;

class APAnalytics
{
    public function track($collection, $items, $userId = null, $params = [])
    {
        $this->collection = $collection;
        $this->items      = $items;
        $this->userId     = $userId;
        $this->params     = $params;

        TrackEvent::dispatch($collection, $items, $userId, $params);
    }

    public function show()
    {
    }
}
