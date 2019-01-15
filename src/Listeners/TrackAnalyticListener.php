<?php

namespace AshPowell\APAnalytics\Listeners;

use AshPowell\APAnalytics\Events\TrackAnalytic;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Log;

class TrackAnalyticListener implements ShouldQueue
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
     * @param  TrackAnalytic  $event
     * @return void
     */
    public function handle(TrackAnalytic $event)
    {
        $connection = $event->mongodb_connection;
        $collection = $event->collection;
        $items      = $event->items;
        $userId     = $event->userId;
        $params     = $event->params;

        $valid = ($items instanceof Collection) ? $items->count() : ($items instanceof Model) ? 1 : count($items);

        if ($valid) {
            $collection = str_plural($collection);
            $items      = array_wrap(($items instanceof Paginator || $items instanceof LengthAwarePaginator) ? $items->items() : $items);
            $postEvent  = in_array($collection, config('apanalytics.format_collections'));
            $event      = $postEvent ? [] : $this->addExtraEventData($items, $userId, $params);

            try {
                if ($postEvent) {
                    foreach ($items as $object) {
                        $basename = strtolower(class_basename($object));

                        $data = [
                            $basename => [
                                'id'   => $object->id ?? null,
                                'type' => $object->type ?? null,
                            ],
                            'business' => [
                                'id' => $object->business->id ?? null,
                            ],
                        ];

                        // Add Extra Stuff
                        $data = $this->addExtraEventData($data, $userId, $params);

                        $event[] = $data;
                    }
                }

                return DB::connection($connection)
                    ->collection($collection)
                    ->insert($event);
            } catch (\Exception $e) {
                Log::error('Error Logging Event', ['error' => $e->getMessage()]);
            }
        }
    }

    private function addExtraEventData($data, $userId, $params)
    {
        // Merge our extra parameters
        if (is_array($params) && count($params)) {
            $data = array_merge($data, $params);
        }

        // Standard stuff
        $data = array_merge($data, [
            'user_id'    => $userId ?? auth()->id() ?? null,
            'updated_at' => mongoTime(),
            'created_at' => mongoTime(),
        ]);

        return $data;
    }
}
