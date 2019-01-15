<?php

namespace AshPowell\APAnalytics;

use App\User;
use AshPowell\APAnalytics\Jobs\Track;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    public function track($collection, $items, $userId = null, $params = [])
    {
        Track::dispatch($collection, $items, $userId, $params);
    }

    public function show($collection, $timeframe = null, $filters = null)
    {
        $start        = $timeframe ? array_get($timeframe, 'start') : null;
        $end          = $timeframe ? array_get($timeframe, 'end') : null;
        $matchArray   = [];
        $filters      = json_decode($filters);

        $model = $this->namespace.studly_case(str_singular($collection)).'Analytic';

        if (! class_exists($model)) {
            throw new InvalidArgumentException("Model {$model} does not exist.");
        }

        if ($filters) {
            foreach ($filters as $filter) {
                $propertyValue = $filter->property_value;

                if (is_numeric($propertyValue)) {
                    $propertyValue = (int) $propertyValue;
                }

                $matchArray = array_merge($matchArray, [$filter->property_name => $propertyValue]);
            }
        }

        abort_unless($this->canViewAnalytic($matchArray, auth()->user()), 403, 'You dont have permission to view these analytics');

        if ($start) {
            $matchArray['created_at']['$gte'] = mongoTime($start);
        }

        if ($end) {
            $matchArray['created_at']['$lt'] = mongoTime($end);
        }

        $data = DB::connection($this->connection)
            ->collection($collection)
            ->raw(function ($query) use ($matchArray) {
                return $query->aggregate([
                    ['$match' => $matchArray],
                    ['$sort' => ['created_at' => 1]],
                ]);
            });

        $data = $this->toModels($data, $model);

        return $data;
    }

    /**
     * Convert the Cursor to Laravel Models.
     *
     * @param [type] $model
     * @param [type] $data
     *
     * @return void
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
     *
     * @return bool
     */
    private function canViewAnalytic($filterArray, User $user)
    {
        $modelsToCheck = config('apanalytics.models_require_ownership');

        if (count($modelsToCheck)) {
            foreach ($modelsToCheck as $model) {
                $modelName = studly_case(str_singular($model));
                $modelId   = array_get($filterArray, strtolower($modelName).'.id');

                if ($modelId) {
                    $modelClass = $this->namespace.$modelName;
                    $model      = $modelClass::find($modelId);

                    if ($model && $user->isOwner($model)) {
                        return true;
                    }

                    return false;
                }
            }
        }

        return true;
    }
}
