<?php

namespace AshPowell\APAnalytics\Jobs;

use AshPowell\APAnalytics\Events\AnalyticTracked;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Attributes\WithoutRelations;

#[WithoutRelations]
class Track implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $mongodb_connection;
    public $table;
    public $items;
    public $userId;
    public $params;
    public $type;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $table
     * @param  mixed  $items
     * @param  mixed  $userId
     * @param  mixed  $params
     * @param  mixed  $type
     * @return void
     */
    public function __construct($table, $items, $userId, $params, $type = 'insert')
    {
        $this->queue = 'analytics';

        $this->mongodb_connection = config('apanalytics.db_connection', 'mongodb');
        $this->table              = $table;
        $this->items              = $items;
        $this->userId             = $userId;
        $this->params             = $params;
        $this->type               = $type;
    }

    public function handle()
    {
        $connection = $this->mongodb_connection;
        $table      = $this->table;
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
            $table     = Str::plural($table);
            $postEvent = in_array($table, config('apanalytics.format_collections'));
            $event     = $this->prepEventData($postEvent, $items, $userId, $params, $table);

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

                            event(new AnalyticTracked($table, $basename, $data));

                            // TODO: Do we need double?
                            // if ($item->business_id) {
                            //     event(new AnalyticTracked($table, 'business', ['business' => ['id' => $item->business_id ?? null]]));
                            // }

                            $event[] = $data;
                        }
                    }

                    // Basic created ie user
                    if (! $postEvent && $table != 'visits') {
                        foreach ($items as $item) {
                            $basename   = strtolower(Str::singular($table));
                            $basenameId = "{$basename}_id";
                            $data       = [
                                $basename => [
                                    'id' => $item->{$basenameId} ?? $item->id ?? null,
                                ],
                            ];

                            event(new AnalyticTracked($table, $basename, $data));

                            // if (is_object($item) && $item->business) {
                            //     event(new AnalyticTracked($table, 'business', ['business' => ['id' => $item->business->id ?? null]]));
                            // }
                        }
                    }

                    return DB::connection($connection)
                        ->table($table)
                        ->insert($event);
                }

                // Type is update
                $basename = strtolower(Str::singular($table));

                return DB::connection($connection)
                        ->table($table)
                        ->where("{$basename}_id", $items)
                        ->update($params);
            } catch (\Exception $e) {
                Log::error('Error Logging Event', ['error' => $e->getMessage(), 'table' => $table, 'items' => $items, 'userId' => $userId, 'params' => $params]);
                report($e);
            }
        }
    }

    private function prepEventData($postEvent, $items, $userId, $params, $table)
    {
        if ($postEvent) {
            return [];
        }

        if (is_array($items) && $table != 'visits') {
            return $items;
        }

        try {
            // Unravel the data
            $data = collect(data_get($items, 'items', $items));

            $formattedItem = ($data->count() === 1) ? $data->first() : $data->toArray();
            return $this->addExtraEventData($formattedItem, $userId, $params);
        } catch (\Exception $e) {
            Log::error('Error Adding Extra Event Data', ['error' => $e->getMessage(), 'postEvent' => $postEvent, 'items' => $items, 'userId' => $userId, 'params' => $params]);
            report($e);
            return $items;
        }
    }

    /**
     * Core stuff to add to the data.
     *
     * @param mixed $data
     * @param mixed $userId
     * @param mixed $params
     * @return mixed
     */
    private function addExtraEventData($data, $userId, $params)
    {
        // If data is just an integer its probably a model ID updating.
        if (is_int($data)) {
            return $data;
        }

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
