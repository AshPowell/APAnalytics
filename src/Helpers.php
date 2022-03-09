<?php

use AshPowell\APAnalytics\APAnalytics;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

if (! function_exists('analytics')) {
    function analytics(): APAnalytics
    {
        return app(APAnalytics::class);
    }
}

if (! function_exists('trackEvent')) {
    /**
     * Access event dispatch of event tracker direct.
     *
     * @param  mixed  $event
     * @param  mixed  $collection
     * @param  mixed  $items
     * @param  mixed  $params
     * @param  null|mixed  $userId
     */
    function trackEvent($collection, $items, $userId = null, $params = [])
    {
        return analytics()->track($collection, $items, $userId, $params);
    }
}

if (! function_exists('showEvents')) {
    /**
     * Access event dispatch of event tracker direct.
     *
     * @param  mixed  $event
     * @param  mixed  $collection
     * @param  mixed  $items
     * @param  mixed  $params
     * @param  null|mixed  $userId
     * @param  null|mixed  $timeframe
     * @param  null|mixed  $filters
     * @param  mixed  $interval
     */
    function showEvents($collection, $interval = 'count', $timeframe = null, $filters = null)
    {
        return analytics()->show($collection, $interval, $timeframe, $filters);
    }
}

if (! function_exists('is_countable')) {
    function is_countable($c)
    {
        return is_array($c) || $c instanceof Countable;
    }
}

if (! function_exists('mongoTime')) {
    /**
     * Returns time in MongoDb Time.
     *
     * @param  null|mixed  $time
     */
    function mongoTime($time = null)
    {
        $time = ($time) ? Carbon::parse($time) : now();

        return new UTCDateTime($time);
    }
}

if (! function_exists('valid_json')) {
    function valid_json($value)
    {
        if (! is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
