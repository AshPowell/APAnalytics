<?php

namespace AshPowell\APAnalytics;

use AshPowell\APAnalytics\Traits\IsAnalytic;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Class AnalyticModel.
 */
class AnalyticModel extends Model
{
    use IsAnalytic;
}
