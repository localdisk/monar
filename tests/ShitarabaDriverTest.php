<?php

namespace Localdisk\Monar\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Localdisk\Monar\Exceptions\MonarException;
use Localdisk\Monar\Monar;
use Localdisk\Monar\ShitarabaDriver;

class ShitarabaDriverTest extends MonarTestCase
{
    private $url = 'https://jbbs.shitaraba.net/bbs/read.cgi/otaku/15956/1470465448/1-20';

    /** @test */
    public function it_can_create_shirabadriver_instance()
    {
        $driver = Monar::bbs($this->url);
        $this->assertInstanceOf(ShitarabaDriver::class, $driver);
    }

    /** @test */
    public function it_can_get_all_messages()
    {
        $client = $this->getHttpMock(new Response(200, [], file_get_contents(__DIR__.'/fixtures/shitaraba/all_messages.txt')));
        $driver = new ShitarabaDriver($this->url, $client);
        $messages = $driver->messages();

        $this->assertInstanceOf(Collection::class, $messages);
        $this->assertCount(3, $messages);
        $message = $messages->last();
        $this->assertEquals([
            'number' => '3',
            'name'   => '◆RejzBcb/WI',
            'email'  => 'sage',
            'date'   => '2016/08/09(火) 17:48:45',
            'body'   => 'ﾃｽ',
            'resid'  => 'xxmuajss0',
            'title'  => '',
        ], $message);
    }

    /** @test */
    public function it_can_empty_response_all_messages()
    {
        $client = $this->getHttpMock(new Response(200, [], ''));
        $driver = new ShitarabaDriver($this->url, $client);
        $messages = $driver->messages();

        $this->assertInstanceOf(Collection::class, $messages);
        $this->assertCount(0, $messages);
    }

    /** @test */
    public function it_can_get_threads()
    {
        $client = $this->getHttpMock(new Response(200, [], file_get_contents(__DIR__.'/fixtures/shitaraba/threads.txt')));
        $driver = new ShitarabaDriver($this->url, $client);
        $threads = $driver->threads();

        $this->assertInstanceOf(Collection::class, $threads);
        $this->assertCount(10, $threads);
        $thread = $threads->last();
        $this->assertEquals([
            'url'   => 'https://jbbs.shitaraba.net/bbs/read.cgi/otaku/15956/1460466373',
            'id'    => '1460466373',
            'title' => 'やる夫が山形城に生まれたようです　３',
            'count' => '517',
        ], $thread);
    }

    /**
     * @test
     */
    public function it_can_get_messages_handle_bbs_notfouond()
    {
        $this->getExpectedException(MonarException::class);
        $this->expectExceptionMessage('url: https://jbbs.shitaraba.net/bbs/rawmode.cgi/otaku/15956/1470465448/1-20. method: GET. error:ERR');
        $client = $this->getHttpMock(new Response(200, ['ERROR' => 'ERR'], ''));
        $driver = new ShitarabaDriver($this->url, $client);
        $driver->messages();
    }

    /** @test */
    public function it_cant_post()
    {
        $client = $this->getHttpMock(new Response(200, [], file_get_contents(__DIR__.'/fixtures/shitaraba/write.txt')));
        $driver = new ShitarabaDriver($this->url, $client);
        $response = $driver->post('てすと', 'sage', 'てすとてすと');

        $this->assertStringContainsString('書きこみが終りました', $response);
    }
}
