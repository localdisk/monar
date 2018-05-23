<?php

namespace Localdisk\Monar;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Support\Collection;
use Localdisk\Monar\Exceptions\MonarException;

class TwoChanDriver extends AbstractDriver
{
    /**
     * @var string
     */
    protected $encoding = 'Shift_JIS';

    /**
     * get threads.
     *
     * @return \Illuminate\Support\Collection
     * @throws MonarException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function threads(): Collection
    {
        $body = $this->request('GET', $this->threadsUrl());

        return $this->parseThreadsCollection($body);
    }

    /**
     * get messages.
     *
     * @param int $start
     * @param int $end
     *
     * @return \Illuminate\Support\Collection
     * @throws MonarException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function messages($start = 1, $end = null): Collection
    {
        $body = $this->request('GET', $this->messagesUrl($start, $end));

        return $this->parseDatCollection($body);
    }

    /**
     * post message.
     *
     * @param string $name
     * @param string $email
     * @param null $text
     *
     * @return mixed|string
     * @throws MonarException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post($name = '', $email = 'sage', $text = null)
    {
        mb_convert_variables('Shift_JIS', 'UTF-8', $name, $email, $text);
        $params = [
            'bbs' => $this->board,
            'key' => $this->thread,
            'time' => time(),
            'FROM' => $name,
            'mail' => $email,
            'MESSAGE' => $text,
            'submit' => $this->encode('書き込む', 'Shift_JIS', 'UTF-8'),
        ];
        $headers = [
            'Host' => parse_url($this->url, PHP_URL_HOST),
            'Referer' => $this->url,
            'User-Agent' => 'Monazilla/1.00',
        ];
        $cookie = new CookieJar();
        $response = $this->request('POST', $this->postUrl(), [
            'headers' => $headers,
            'form_params' => $params,
            'cookies' => $cookie,
        ]);

        if ($this->confirm($response)) {
            $cookie->setCookie(SetCookie::fromString('IS_COOKIE=1'));
            $response = $this->request('POST', $this->postUrl(), [
                'headers' => $headers,
                'form_params' => $params,
                'cookies' => $cookie,
            ]);
        }

        return $response;
    }

    /**
     * parse url.
     *
     * @return void
     */
    protected function parse(): void
    {
        $parsed = parse_url($this->url);
        $paths = $this->renewArray(explode('/', parse_url($this->url, PHP_URL_PATH)));

        $this->baseUrl = $parsed['scheme'].'://'.$parsed['host'];
        $this->category = '';

        if (\count($paths) === 1) {
            $this->board = $paths[0];
            $this->thread = '';
        } else {
            $this->board = $paths[2];
            $this->thread = $paths[3];
        }
    }

    /**
     * parse dat collection.
     *
     * @param string $body
     *
     * @return \Illuminate\Support\Collection
     */
    protected function parseDatCollection($body): Collection
    {
        $lines = array_filter(explode("\n", $body), '\strlen');
        $number = 0;

        return collect(array_map(function ($line) use (&$number) {
            $number++;
            [$name, $email, $date, $body] = explode('<>', $line);
            $name = trim(strip_tags($name));
            $body = strip_tags($body, '<br>');
            $resid = mb_substr($date, strpos($date, ' ID:') + 2);
            $date = mb_substr($date, 0, strpos($date, ' ID:') - 2);

            return compact('number', 'name', 'email', 'date', 'body', 'resid');
        }, $lines));
    }

    /**
     * parse threads collection.
     *
     * @param $body
     *
     * @return \Illuminate\Support\Collection
     */
    protected function parseThreadsCollection($body): Collection
    {
        $threads = array_filter(explode("\n", $body), '\strlen');

        return collect(array_map(function ($elem) {
            [$id, $tmp] = explode('.dat<>', $elem);
            preg_match('/^(.*)\((\d+)\)\z/', $tmp, $matches);

            return [
                'url' => vsprintf('http://%s/test/read.cgi/%s/%d', [
                    parse_url($this->url, PHP_URL_HOST),
                    $this->board,
                    $id,
                ]),
                'id' => $id,
                'title' => trim($matches[1]),
                'count' => $matches[2],
            ];
        }, $threads));
    }

    /**
     * build message url.
     *
     * @param int $start
     * @param int| null $end
     *
     * @return string
     */
    protected function messagesUrl($start = 1, $end = null): string
    {
        return "{$this->baseUrl}/{$this->board}/dat/{$this->thread}.dat";
    }

    /**
     * build thread url.
     *
     * @return string
     */
    protected function threadsUrl(): string
    {
        return "{$this->baseUrl}/{$this->board}/subject.txt";
    }

    /**
     * build post url.
     *
     * @return string
     */
    protected function postUrl(): string
    {
        return "{$this->baseUrl}/test/bbs.cgi";
    }

    /**
     * 書き込み確認かどうか.
     *
     * @param  string $html
     *
     * @return bool
     */
    private function confirm($html): bool
    {
        return strpos($html, '書き込み確認') !== false;
    }
}
