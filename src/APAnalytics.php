<?php

namespace AshPowell\APAnalytics;

use AshPowell\APAnalytics\Jobs\Track;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
     * @param  mixed  $table
     * @param  mixed  $items
     * @param  null|mixed  $userId
     * @param  mixed  $params
     * @return void
     */
    public function track($table, $items, $userId = null, $params = [])
    {
        $items = $this->formatItems($items);

        try {
            Track::dispatch($table, $items, $userId, $params);

            return true;
        } catch (\Exception $e) {
            report($e);

            return null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            report($e);

            return null;
        }
    }

    public function update($table, $item, $params)
    {
        try {
            Track::dispatch($table, $item, null, $params, 'update');

            return true;
        } catch (\Exception $e) {
            report($e);

            return null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            report($e);

            return null;
        }
    }

    /**
     * Get the Analytics.
     *
     * @param  mixed  $table
     * @param  null|mixed  $timeframe
     * @param  null|mixed  $filters
     * @param  mixed  $interval
     * @param  mixed  $groupBy
     * @param  null|mixed  $distinct
     */
    public function show($table, $interval = 'count', $timeframe = null, $filters = null, $groupBy = null, $distinct = null)
    {
        try {
            $start          = $timeframe ? Arr::get($timeframe, 'start') : null;
            $end            = $timeframe ? Arr::get($timeframe, 'end') : null;
            $matchArray     = [];
            $filters        = valid_json($filters) ? (array) json_decode($filters) : $filters;
            $intervalFormat = '%Y-%m-%dT%H';
            $aggregate      = [];
            $model          = $this->namespace.Str::studly(Str::singular($table)).'Analytic';

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
                abort_unless(auth()->check() && auth()->user()->can('view', [new $model, $matchArray]), 403, 'You dont have permission to view these analytics');
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

            $data = $model::raw(function ($table) use ($matchArray, $interval, $aggregate, $groupBy) {
                if ($interval == 'count' && ! $groupBy) {
                    return $table->count($matchArray);
                }

                if ($aggregate) {
                    return $table->aggregate($aggregate, ['allowDiskUse' => true]);
                }
            });

            return $data;
        } catch (\Exception $e) {
            report($e);

            return null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            report($e);

            return null;
        }
    }

    private function matchPropertyNameToValue($matchArray, $filter)
    {
        $propertyValue = data_get($filter, 'property_value', reset($filter));

        if (is_numeric($propertyValue)) {
            $propertyValue = (int) $propertyValue;
        }

        return array_merge($matchArray, [data_get($filter, 'property_name', key($filter)) => $propertyValue]);
    }

    /**
     * Format the items to be tracked.
     * @param mixed $items
     * @return mixed
     */
    private function formatItems($items)
    {
        if ($items instanceof Paginator || $items instanceof LengthAwarePaginator || $items instanceof CursorPaginator) {
            $items = $items->items(); // Convert paginated items to an array
        }

        // Handle collections and arrays
        if (is_array($items) || $items instanceof Collection) {
            return $this->filterAttributes($items);
        }

        // For a single item, wrap it in an array
        return Arr::wrap($items);
    }

    // Needs work - not ready yet.
    private function filterAttributes($items)
    {
        return $items;
        // return collect($items)->map(function ($item) {
        //     if ($item instanceof Model) {
        //         $attributesToBeLogged = $item->attributesToBeLogged();

        //         // Return a new instance with filtered attributes directly
        //         return new $item(
        //             collect($item->getAttributes())->only($attributesToBeLogged)->toArray()
        //         );
        //     }

        //     // Return non-model items as-is
        //     return $item;
        // });
    }
}
