<?php

namespace Localdisk\Monar;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Localdisk\Monar\Exceptions\MonarException;

abstract class AbstractDriver implements Driver
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $category;

    /**
     * @var string
     */
    protected $board;

    /**
     * @var string
     */
    protected $thread;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $encoding;

    /**
     * @var string
     */
    protected $cookie;

    /**
     * AbstractDriver constructor.
     *
     * @param  string  $url
     * @param  Client  $client
     */
    public function __construct(string $url, Client $client)
    {
        $this->url = $url;
        $this->client = $client;
        $this->parse();
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getBoard(): string
    {
        return $this->board;
    }

    /**
     * @return string
     */
    public function getThread(): string
    {
        return $this->thread;
    }

    /**
     * renew array.
     *
     * @param  array  $array
     * @return array
     */
    protected function renewArray(array $array): array
    {
        return array_values(array_filter($array));
    }

    /**
     * send request.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     * @return mixed|string
     *
     * @throws MonarException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request(string $method, string $url, array $options = [])
    {
        $response = $this->client->request($method, $url, $options);

        $error = $response->getHeader('ERROR');

        if (! empty($error)) {
            $errorStr = \is_array($error) ? implode(',', $error) : $error;
            throw new MonarException("url: {$url}. method: {$method}. error:{$errorStr}");
        }

        if ($cookie = $response->getHeader('Set-Cookie')) {
            $this->cookie = $cookie[0];
        }

        return $this->encode($response->getBody(), $this->encoding, 'UTF-8');
    }

    /**
     * encode body text.
     *
     * @param  string  $body
     * @param  string  $from
     * @param  string  $to
     * @return mixed|string
     */
    protected function encode(string $body, string $from, string $to)
    {
        return mb_convert_encoding($body, $to, $from);
    }

    /**
     * parse url.
     *
     * @return void
     */
    abstract protected function parse(): void;

    /**
     * parse threads collection.
     *
     * @param  string  $body
     * @return \Illuminate\Support\Collection
     */
    abstract protected function parseThreadsCollection(string $body): Collection;

    /**
     * build thread url.
     *
     * @return string
     */
    abstract protected function threadsUrl(): string;

    /**
     * build post url.
     *
     * @return string
     */
    abstract protected function postUrl(): string;
}
