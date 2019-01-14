<?php

namespace AshPowell\APAnalytics\Tests;

use AshPowell\APAnalytics\APAnalyticsServiceProvider;
use AshPowell\APAnalytics\Facades\APAnalytics;
use Orchestra\Testbench\TestCase;

class APAnalyticsTest extends TestCase
{
    public function testExample()
    {
        $this->assertEquals(1, 1);
    }

    protected function getPackageProviders($app)
    {
        return [APAnalyticsServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'apanalytics' => APAnalytics::class,
        ];
    }
}
