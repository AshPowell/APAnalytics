<?php

namespace AshPowell\APAnalytics\Facades;

use Illuminate\Support\Facades\Facade;

class APAnalytics extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'apanalytics';
    }
}
