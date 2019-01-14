<?php

namespace AshPowell\APAnalytics\Jobs;

use \Illuminate\Bus\Queueable;
use \Illuminate\Contracts\Queue\ShouldQueue;
use \Illuminate\Foundation\Bus\Dispatchable;
use \Illuminate\Queue\InteractsWithQueue;
use \Illuminate\Queue\SerializesModels;

use \Illuminate\Database\Eloquent\Collection;
use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Pagination\LengthAwarePaginator;
use \Illuminate\Pagination\Paginator;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Log;

class TrackEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $this->queue = 'analytics';

        $this->collection = $collection;
        $this->items      = $items;
        $this->userId     = $userId;
        $this->params     = $params;
    }

    public function handle()
    {
        $valid = ($items instanceof Collection) ? $items->count() : ($items instanceof Model) ? 1 : count($items);

        if ($valid) {
            $collection = str_plural($collection);
            $items      = array_wrap(($items instanceof Paginator || $items instanceof LengthAwarePaginator) ? $items->items() : $items);
            $postEvent  = in_array($collection, ['views', 'impressions', 'claims']);
            $event      = $postEvent ? [] : $this->addExtraEventData($items, $userId, $params);

            //try {
                if ($postEvent) {
                    foreach ($items as $object) {
                        $basename = strtolower(class_basename($object));

                        $data = [
                            $basename => [
                                'id'   => $object->id ?? null,
                                'type' => $object->type ?? null
                            ],
                            'business' => [
                                'id' => $object->business->id ?? null
                            ]
                        ];

                        // Add Extra Stuff
                        $data = $this->addExtraEventData($data, $userId, $params);

                        $event[] = $data;
                    }
                }

                return DB::connection('mongodb')
                    ->collection($collection)
                    ->insert($event);
            //} catch (\Exception $e) {
            //    Log::error('Error Logging Event', ['error' => $e->getMessage()]);
            //}
        }
    }
}
