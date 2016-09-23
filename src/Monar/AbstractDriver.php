<?php

namespace Localdisk\Monar;

use GuzzleHttp\Client;
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
     * @param $url
     * @param Client $client
     */
    public function __construct($url, Client $client)
    {
        $this->url = $url;
        $this->client = $client;
        $this->parse();
    }

    /**
     * renew array.
     *
     * @param array $array
     *
     * @return array
     */
    protected function renewArray(array $array)
    {
        return array_values(array_filter($array));
    }

    /**
     * send request.
     *
     * @param $method
     * @param $url
     * @param array $options
     *
     * @return mixed|string
     * @throws MonarException
     */
    protected function request($method, $url, array $options = [])
    {
        $response = $this->client->request($method, $url, $options);

        $error = $response->getHeader('ERROR');
        if (! empty($error)) {
            $errorStr = is_array($error) ? implode(',', $error) : $error;
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
     * @param $body
     * @param $from
     * @param $to
     *
     * @return mixed|string
     */
    protected function encode($body, $from, $to)
    {
        return mb_convert_encoding($body, $to, $from);
    }

    /**
     * parse url.
     *
     * @return void
     */
    abstract protected function parse();

    /**
     * parse dat collection.
     *
     * @param string $body
     *
     * @return \Illuminate\Support\Collection
     */
    abstract protected function parseDatCollection($body);

    /**
     * parse threads collection.
     *
     * @param $body
     *
     * @return \Illuminate\Support\Collection
     */
    abstract protected function parseThreadsCollection($body);

    /**
     * build message url.
     *
     * @param int $start
     * @param int| null $end
     *
     * @return string
     */
    abstract protected function messagesUrl($start = 1, $end = null);

    /**
     * build thread url.
     *
     * @return string
     */
    abstract protected function threadsUrl();

    /**
     * build post url.
     *
     * @return string
     */
    abstract protected function postUrl();
}
