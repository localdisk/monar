<?php

namespace Localdisk\Monar\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Localdisk\Monar\MonarProvider;
use Orchestra\Testbench\TestCase;

class MonarTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($application)
    {
        return [MonarProvider::class];
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
