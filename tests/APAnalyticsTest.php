<?php

namespace AshPowell\APAnalytics\Tests;

use AshPowell\APAnalytics\Facades\APAnalytics;
use AshPowell\APAnalytics\ServiceProvider;
use Orchestra\Testbench\TestCase;

class APAnalyticsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'apanalytics' => APAnalytics::class,
        ];
    }

    public function testExample()
    {
        $this->assertEquals(1, 1);
    }
}
