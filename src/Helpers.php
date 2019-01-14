<?php

use \MongoDB\BSON\UTCDateTime;
use AshPowell\APAnalytics\APAnalytics;
use Illuminate\Support\Carbon;

if (! function_exists('analytics')) {
    function analytics(): APAnalytics
    {
        return app(APAnalytics::class);
    }
}

if (! function_exists('trackEvent')) {
    /**
     * Access event dispatch of event tracker direct
     * @param mixed      $event
     * @param mixed      $collection
     * @param mixed      $items
     * @param mixed      $params
     * @param null|mixed $userId
     */
    function trackEvent($collection, $items, $userId = null, $params = [])
    {
        return analytics()->track($collection, $items, $userId, $params);
    }
}

if (! function_exists('showEvents')) {
    /**
     * Access event dispatch of event tracker direct
     * @param mixed      $event
     * @param mixed      $collection
     * @param mixed      $items
     * @param mixed      $params
     * @param null|mixed $userId
     */
    function showEvents($collection, $timeframe = null, $filters = null)
    {
        return analytics()->show($collection, $timeframe, $filters);
    }
}

if (!function_exists('is_countable')) {
    function is_countable($c)
    {
        return is_array($c) || $c instanceof Countable;
    }
}

if (! function_exists('mongoTime')) {
    /**
     * Returns time in MongoDb Time
     * @param \DateTime|string|null $time
     */
    function mongoTime($time = null)
    {
        $time = ($time) ? Carbon::parse($time) : now();

        return new UTCDateTime($time);
    }
}
