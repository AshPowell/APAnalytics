<?php

namespace AshPowell\APAnalytics\Jobs;

use AshPowell\APAnalytics\Events\AnalyticTracked;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Log;

class Track implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $mongodb_connection;
    public $collection;
    public $items;
    public $userId;
    public $params;
    public $type;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $collection
     * @param  mixed  $items
     * @param  mixed  $userId
     * @param  mixed  $params
     * @param  mixed  $type
     * @return void
     */
    public function __construct($collection, $items, $userId, $params, $type = 'insert')
    {
        $this->queue = 'analytics';

        $this->mongodb_connection = config('apanalytics.db_connection', 'mongodb');
        $this->collection         = $collection;
        $this->items              = $items;
        $this->userId             = $userId;
        $this->params             = $params;
        $this->type               = $type;
    }

    public function handle()
    {
        $connection = $this->mongodb_connection;
        $collection = $this->collection;
        $items      = $this->items;
        $userId     = $this->userId;
        $params     = $this->params;
        $type       = $this->type;
        $valid      = true;

        if ($type != 'update') {
            if ($items != null) {
                $valid = (($items instanceof Collection) ? $items->count() : ($items instanceof Model)) ? 1 : count($items);
            } else {
                $valid = false;
            }
        }

        if ($valid) {
            $collection = Str::plural($collection);
            $items      = $this->formatItems($items);
            $postEvent  = in_array($collection, config('apanalytics.format_collections'));
            $event      = $this->prepEventData($postEvent, $items, $userId, $params, $collection);

            try {
                if ($type == 'insert') {
                    if ($postEvent) {
                        foreach ($items as $item) {
                            $basename   = strtolower(class_basename($item));
                            $basenameId = "{$basename}_id";
                            $data       = [
                                $basename => [
                                    'id'   => $item->id ?? $item->{$basenameId} ?? null,
                                    'type' => $item->type ?? null,
                                ],
                            ];

                            if ($item->business_id) {
                                $data = array_merge($data, ['business' => [
                                    'id' => $item->business_id ?? null,
                                ]]);
                            }

                            // Add Extra Stuff
                            $data = $this->addExtraEventData($data, $userId, $params);

                            event(new AnalyticTracked($collection, $basename, $data));

                            if ($item->business_id) {
                                event(new AnalyticTracked($collection, 'business', ['business' => ['id' => $item->business_id ?? null]]));
                            }

                            $event[] = $data;
                        }
                    }

                    // Basic created ie user
                    if (! $postEvent && $collection != 'visits') {
                        foreach ($items as $item) {
                            $basename   = strtolower(Str::singular($collection));
                            $basenameId = "{$basename}_id";
                            $data       = [
                                $basename => [
                                    'id' => $item->{$basenameId} ?? $item->id ?? null,
                                ],
                            ];

                            event(new AnalyticTracked($collection, $basename, $data));

                            // if (is_object($item) && $item->business) {
                            //     event(new AnalyticTracked($collection, 'business', ['business' => ['id' => $item->business->id ?? null]]));
                            // }
                        }
                    }

                    return DB::connection($connection)
                        ->collection($collection)
                        ->insert($event);
                }

                // Type is update
                $basename = strtolower(Str::singular($collection));

                return DB::connection($connection)
                        ->collection($collection)
                        ->where("{$basename}_id", $items)
                        ->update($params);
            } catch (\Exception $e) {
                Log::error('Error Logging Event', ['error' => $e->getMessage()]);
                report($e);
            }
        }
    }

    private function prepEventData($postEvent, $items, $userId, $params, $collection)
    {
        if ($postEvent) {
            return [];
        }

        if (is_array($items) && $collection != 'visits') {
            return $items;
        }

        return $this->addExtraEventData($items, $userId, $params);
    }

    private function formatItems($items)
    {
        $formattedItems = $items;

        if (is_array($formattedItems) || $items instanceof Collection) {
            return $formattedItems;
        }

        if ($items instanceof Paginator || $items instanceof LengthAwarePaginator || $items instanceof CursorPaginator) {
            $formattedItems = $items->items();
        }

        return Arr::wrap($formattedItems);
    }

    private function addExtraEventData($data, $userId, $params)
    {
        // Merge our extra parameters
        if (is_array($params) && count($params)) {
            $data = array_merge($data, $params);
        }

        // Standard stuff
        $data = array_merge($data, [
            'user_id' => $userId ?? auth()->id() ?? null,
        ]);

        data_fill($data, 'created_at', mongoTime());

        return $data;
    }
}
