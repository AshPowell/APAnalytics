<?php

namespace AshPowell\APAnalytics;

use AshPowell\APAnalytics\Jobs\Track;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

class APAnalytics
{
    protected $connection;
    protected $namespace;

    /**
     * Instantiate a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->connection = config('apanalytic.db_connection', 'mongodb');
        $this->namespace  = config('apanalytic.namespace', '\App\\');
    }

    /**
     * Track the Analytic.
     *
     * @param  mixed  $collection
     * @param  mixed  $items
     * @param  null|mixed  $userId
     * @param  mixed  $params
     * @return void
     */
    public function track($collection, $items, $userId = null, $params = [])
    {
        Track::dispatch($collection, $items, $userId, $params);

        return true;
    }

    public function update($collection, $item, $params)
    {
        Track::dispatch($collection, $item, null, $params, 'update');

        return true;
    }

    /**
     * Get the Analytics.
     *
     * @param  mixed  $collection
     * @param  null|mixed  $timeframe
     * @param  null|mixed  $filters
     * @param  mixed  $interval
     * @param  mixed  $groupBy
     * @param  null|mixed  $distinct
     */
    public function show($collection, $interval = 'count', $timeframe = null, $filters = null, $groupBy = null, $distinct = null)
    {
        $start          = $timeframe ? Arr::get($timeframe, 'start') : null;
        $end            = $timeframe ? Arr::get($timeframe, 'end') : null;
        $matchArray     = [];
        $filters        = valid_json($filters) ? (array) json_decode($filters) : $filters;
        $intervalFormat = '%Y-%m-%dT%H';
        $aggregate      = [];
        $model          = $this->namespace.Str::studly(Str::singular($collection)).'Analytic';

        if (! class_exists($model)) {
            throw new InvalidArgumentException("Model {$model} does not exist.");
        }

        if ($filters) {
            if (is_array($filters)) {
                $filters = Arr::first($filters);

                if (is_array($filters) && count($filters) > 1) {
                    foreach ($filters as $filter) {
                        $matchArray = $this->matchPropertyNameToValue($matchArray, $filter);
                    }
                } else {
                    $matchArray = $this->matchPropertyNameToValue($matchArray, $filters);
                }
            }
        }

        if (! app()->runningInConsole()) {
            abort_unless(auth()->check() && auth()->user()->can('view', [(new $model), $matchArray]), 403, 'You dont have permission to view these analytics');
        }

        if ($start) {
            $matchArray['created_at']['$gte'] = mongoTime($start);
        }

        if ($end) {
            $matchArray['created_at']['$lt'] = mongoTime($end);
        }

        if ($matchArray) {
            $aggregate[] = ['$match' => $matchArray];
        }

        if ($distinct) {
            $aggregate[] =  [
                '$group' => [
                    '_id'        => '$'.$distinct,
                    'created_at' => [
                        '$last' => '$created_at',
                    ],
                ],
            ];
        }

        if ($interval != 'count') {
            if ($interval == 'daily') {
                $intervalFormat = '%Y-%m-%d';
            }

            if ($interval == 'weekly') {
                $intervalFormat = '%Y-%U';
            }

            if ($interval == 'monthly' || $interval == 'growth') {
                $intervalFormat = '%Y-%m';
            }

            $aggregate[] =  [
                '$group' => [
                    '_id' => [
                        '$dateToString' => ['date' => '$created_at', 'format' => $intervalFormat],
                    ],
                    'count' => [
                        '$sum' => 1,
                    ],
                    'created_at' => [
                        '$last' => '$created_at',
                    ],
                ],
            ];

            $aggregate[] = ['$sort' => ['created_at' => 1]];

            $aggregate[] = [
                '$project' => [
                    '_id'        => 0,
                    'created_at' => 1,
                    'count'      => 1,
                ],
            ];
        }

        if ($interval == 'count' && $groupBy != null) {
            $nested      = Str::contains($groupBy, '.');
            $group       = $nested ? Str::before($groupBy, '.') : $groupBy;
            $nestedGroup = $nested ? Str::after($groupBy, '.') : $groupBy;

            if ($nested) {
                $aggregate[] = ['$unwind' => '$'.$group];
            }

            $aggregate[] =  [
                '$group' => [
                    '_id'   => '$'.$groupBy,
                    'count' => [
                        '$sum' => 1,
                    ],
                ],
            ];

            $aggregate[] = ['$sort' => ['count' => 1]];

            $aggregate[] = [
                '$project' => [
                    '_id'        => 0,
                    $nestedGroup => '$_id',
                    'count'      => 1,
                ],
            ];
        }

        $data = $model::raw(function ($collection) use ($matchArray, $interval, $aggregate, $groupBy) {
            if ($interval == 'count' && ! $groupBy) {
                return $collection->count($matchArray);
            }

            if ($aggregate) {
                return $collection->aggregate($aggregate, ['allowDiskUse' => true]);
            }
        });

        return $data;
    }

    private function matchPropertyNameToValue($matchArray, $filter)
    {
        $propertyValue = data_get($filter, 'property_value', reset($filter));

        if (is_numeric($propertyValue)) {
            $propertyValue = (int) $propertyValue;
        }

        return array_merge($matchArray, [data_get($filter, 'property_name', key($filter)) => $propertyValue]);
    }
}
