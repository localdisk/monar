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
     * @param int|null $start
     * @param int|null $end
     *
     * @return \Illuminate\Support\Collection
     * @throws MonarException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function messages(?int $start = null, ?int $end = null): Collection
    {
        $body = $this->request('GET', $this->messagesUrl($start, $end));

        return $this->parseDatCollection($body, $end);
    }

    /**
     * post message.
     *
     * @param string $name
     * @param string $email
     * @param string|null $text
     *
     * @return mixed|string
     * @throws MonarException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post(string $name = '', string $email = 'sage', ?string $text = null)
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
    protected function parseDatCollection(string $body, ?int $end = null): Collection
    {
        $lines = array_filter(explode("\n", $body), '\strlen');
        $number = 0;

        $lineCount = count($lines);

        if (null === $end || $end > $lineCount) {
            $end = $lineCount;
        }

        $collection = collect();

        for ($number = 1; $number <= $end; $number++) {
            $line = $lines[$number - 1];

            [$name, $email, $date, $body] = explode('<>', $line);
            $name = trim(strip_tags($name));
            $body = strip_tags($body, '<br>');
            $resid = mb_substr($date, strpos($date, ' ID:') + 2);
            $date = mb_substr($date, 0, strpos($date, ' ID:') - 2);

            $collection->push(compact('number', 'name', 'email', 'date', 'body', 'resid'));
        }

        return $collection;
    }

    /**
     * parse threads collection.
     *
     * @param string $body
     *
     * @return \Illuminate\Support\Collection
     */
    protected function parseThreadsCollection(string $body): Collection
    {
        $threads = array_filter(explode("\n", $body), '\strlen');

        return collect(array_map(function($elem) {
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
     * @param int|null $end
     *
     * @return string
     */
    protected function messagesUrl(int $start = 1, ?int $end = null): string
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
    private function confirm(string $html): bool
    {
        return strpos($html, '書き込み確認') !== false;
    }
}
