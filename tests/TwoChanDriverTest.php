<?php

namespace Localdisk\Monar\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Localdisk\Monar\Monar;
use Localdisk\Monar\TwoChanDriver;

class TwoChanDriverTest extends MonarTestCase
{
    private $url = 'http://ex14.vip2ch.com/test/read.cgi/news4ssnip/1471964860/';

    /**
     * @test
     * @group twochan
     */
    public function it_can_create_twochandriver_instance()
    {
        $driver = Monar::bbs($this->url);
        $this->assertInstanceOf(TwoChanDriver::class, $driver);
    }

    /**
     * @test
     * @group twochan
     */
    public function it_can_get_threads()
    {
        $client = $this->getHttpMock(new Response(200, [], file_get_contents(__DIR__.'/fixtures/2ch/threads.txt')));
        $driver = new TwoChanDriver($this->url, $client);
        $threads = $driver->threads();

        $this->assertInstanceOf(Collection::class, $threads);
        $this->assertCount(10, $threads);
        $thread = $threads->last();
        $this->assertEquals([
            'id'    => '1473564844',
            'title' => '神谷奈緒「チャット」',
            'count' => '439',
        ], $thread);
    }

    /**
     * @test
     * @group twochan
     */
    public function it_can_get_all_messages()
    {
        $client = $this->getHttpMock(new Response(200, [], file_get_contents(__DIR__.'/fixtures/2ch/all_messages.txt')));
        $driver = new TwoChanDriver($this->url, $client);
        $messages = $driver->messages();

        $this->assertInstanceOf(Collection::class, $messages);
        $this->assertCount(4, $messages);
        $message = $messages->last();
        $this->assertEquals([
            'number' => 4,
            'name'   => '◆yTbGYPG1Vfkq',
            'email'  => 'sage',
            'date'   => '2016/08/24(水) 15:44:05.21',
            'body'   => ' テスト <br>  ',
            'resid'  => 'I14zMNd30',
        ], $message);
    }

    /**
     * @test
     * @group twochan
     */
    public function it_can_empty_response_all_messages()
    {
        $client = $this->getHttpMock(new Response(200, [], ''));
        $driver = new TwoChanDriver($this->url, $client);
        $messages = $driver->messages();

        $this->assertInstanceOf(Collection::class, $messages);
        $this->assertCount(0, $messages);
    }

    /**
     * @test
     * @group twochan
     */
    public function it_cant_post()
    {
        $client = $this->getHttpMock(new Response(200, [], file_get_contents(__DIR__.'/fixtures/2ch/write.txt')));
        $driver = new TwoChanDriver($this->url, $client);
        $response = $driver->post('てすと', 'sage', 'てすとてすと');

        $this->assertContains('書きこみました', $response);
    }
}
