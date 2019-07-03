<?php

namespace AshPowell\APAnalytics;

use App\User;
use AshPowell\APAnalytics\Jobs\Track;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;

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
     * @return void
     * @param  mixed      $collection
     * @param  mixed      $items
     * @param  null|mixed $userId
     * @param  mixed      $params
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
     * @param mixed      $collection
     * @param null|mixed $timeframe
     * @param null|mixed $filters
     * @param mixed      $interval
     * @param mixed      $groupBy
     */
    public function show($collection, $interval = 'count', $timeframe = null, $filters = null, $groupBy = null)
    {
        $start          = $timeframe ? Arr::get($timeframe, 'start') : null;
        $end            = $timeframe ? Arr::get($timeframe, 'end') : null;
        $matchArray     = [];
        $filters        = valid_json($filters) ? json_decode($filters) : $filters;
        $intervalFormat = '%Y-%m-%dT%H';
        $aggregate      = [];
        $model          = $this->namespace.Str::studly(Str::singular($collection)).'Analytic';

        if (! class_exists($model)) {
            throw new InvalidArgumentException("Model {$model} does not exist.");
        }

        if ($filters) {
            foreach ($filters as $filter) {
                if (is_array($filter)) {
                    $matchArray = array_merge($matchArray, $filter);
                } else {
                    $propertyValue = $filter->property_value;

                    if (is_numeric($propertyValue)) {
                        $propertyValue = (int) $propertyValue;
                    }

                    $matchArray = array_merge($matchArray, [$filter->property_name => $propertyValue]);
                }
            }
        }

        abort_unless(auth()->check() && $this->canViewAnalytic($model, $matchArray, auth()->user()), 403, 'You dont have permission to view these analytics');

        if ($start) {
            $matchArray['created_at']['$gte'] = mongoTime($start);
        }

        if ($end) {
            $matchArray['created_at']['$lt'] = mongoTime($end);
        }

        if ($matchArray) {
            $aggregate[] = ['$match' => $matchArray];
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
            $nested = Str::contains($groupBy, '.');

            if ($nested) {
                $aggregate[] = ['$unwind' => '$'.Str::before($groupBy, '.')];
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
                    '_id'   => 0,
                    Str::after($groupBy, '.')  => '$_id',
                    'count' => 1,
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

    /**
     * Convert the Cursor to Laravel Models.
     *
     * @return void
     * @param  mixed      $data
     * @param  null|mixed $model
     */
    private function toModels($data, $model = null)
    {
        if (! $model) {
            $model = '\Jenssegers\Mongodb\Eloquent\Model';
        }

        if (! class_exists($model)) {
            throw new InvalidArgumentException("Model {$model} does not exist.");
        }

        if ($data instanceof Cursor) {
            // Convert MongoCursor results to a collection of models.
            $data = iterator_to_array($data, false);

            return $model::hydrate($data);
        } elseif ($data instanceof BSONDocument) {
            // Convert Mongo BSONDocument to a single object.
            $data = $data::getArrayCopy();

            return $model::newFromBuilder((array) $data);
        } elseif (is_array($data) && array_key_exists('_id', $data)) {
            // The result is a single object.
            return $model::newFromBuilder((array) $data);
        }

        return $data;
    }

    /**
     * Check specified user has permission to see these analytics.
     *
     * @param array $filterArray
     * @param User  $user
     * @param mixed $analyticModel
     *
     * @return bool
     */
    private function canViewAnalytic($analyticModel, $filterArray, User $user = null)
    {
        if (app()->runningInConsole()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $modelsToCheck = config('apanalytics.models_require_ownership');

        if (count($modelsToCheck)) {
            foreach ($modelsToCheck as $model) {
                $modelName  = Str::studly(Str::singular($model));
                $modelId    = Arr::get($filterArray, strtolower($modelName).'.id');
                $modelClass = $this->namespace.$modelName;

                if ($modelId) {
                    $model = $modelClass::find($modelId);

                    if ($model && ! $model->canViewAnalytic($user)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return (new $analyticModel)->canViewCollection($user);
    }
}
