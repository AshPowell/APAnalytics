<?php

namespace AshPowell\APAnalytics;

use AshPowell\APAnalytics\Traits\IsAnalytic;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * Class AnalyticModel.
 */
class AnalyticModel extends Model
{
    use IsAnalytic;
}
