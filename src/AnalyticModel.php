<?php

namespace AshPowell\APAnalytics;

use AshPowell\APAnalytics\Traits\isAnalytic;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * Class AnalyticModel
 */
class AnalyticModel extends Model
{
    use isAnalytic;
}
