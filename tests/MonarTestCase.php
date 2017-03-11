<?php

namespace Localdisk\Monar\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;
use GuzzleHttp\Handler\MockHandler;

class MonarTestCase extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    protected function getPackageProviders($application)
    {
        return [\Localdisk\Monar\MonarProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Monar' => 'Localdisk\Monar\Monar',
        ];
    }

    protected function getHttpMock(Response $response)
    {
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);

        return new Client(['handler' => $handler]);
    }
}
