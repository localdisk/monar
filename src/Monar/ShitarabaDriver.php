<?php

namespace Localdisk\Monar;

use Illuminate\Support\Collection;
use Localdisk\Monar\Exceptions\MonarException;

class ShitarabaDriver extends AbstractDriver
{
    /**
     * @var string
     */
    protected $baseUrl = 'http://jbbs.shitaraba.net';

    /**
     * @var string
     */
    protected $encoding = 'EUC-JP';

    /**
     * get threads.
     *
     * @return \Illuminate\Support\Collection
     * @throws MonarException
     */
    public function threads()
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
    public function messages($start = null, $end = null): Collection
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
        mb_convert_variables('EUC-JP', 'UTF-8', $name, $email, $text);
        $params = [
            'submit'  => $this->encode('書き込む', 'EUC-JP', 'UTF-8'),
            'DIR'     => $this->category,
            'BBS'     => $this->board,
            'KEY'     => $this->thread,
            'TIME'    => time(),
            'MESSAGE' => $text,
            'NAME'    => $name,
            'MAIL'    => $email,
        ];
        $bytes = 0;
        foreach ($params as $param) {
            $bytes += \strlen($param);
        }
        $headers = [
            'Host'           => parse_url($this->url, PHP_URL_HOST),
            'Referer'        => $this->url,
            'Content-Length' => $bytes,
            'User-Agent' => 'Monazilla/1.00',
        ];

        $response = $this->request('POST', $this->postUrl(), [
            'headers'     => $headers,
            'form_params' => $params,
        ]);

        if ($this->isError($response)) {
            throw new MonarException($response);
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
        $paths = $this->renewArray(explode('/', parse_url($this->url, PHP_URL_PATH)));
        if ($paths[1] === 'read.cgi' || $paths[1] === 'read_archive.cgi') {
            $this->category = $paths[2];
            $this->board = $paths[3];
            $this->thread = $paths[4];
        } else {
            $this->category = $paths[0];
            $this->board = $paths[1];
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

        return collect(array_map(function ($line) {
            [$number, $name, $email, $date, $body, , $resid] = explode('<>', $line);
            $name = trim(strip_tags($name));
            $body = strip_tags($body, '<br>');

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
            [$id, $tmp] = explode('.cgi,', $elem);
            preg_match('/^(.*)\((\d+)\)\z/', $tmp, $matches);

            return [
                'url'   => vsprintf('http://%s/bbs/read.cgi/%s/%s/%d', [
                    parse_url($this->url, PHP_URL_HOST),
                    $this->category,
                    $this->board,
                    $id,
                ]),
                'id'    => $id,
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
        $url = "{$this->baseUrl}/bbs/rawmode.cgi/{$this->category}/{$this->board}/{$this->thread}/";
        if (null !== $start && null !== $end) {
            return $url."{$start}-{$end}";
        }
        if (null !== $start && null === $end) {
            return $url."{$start}-";
        }
        if (null === $start && null !== $end) {
            return $url."-{$end}";
        }

        return $url;
    }

    /**
     * build thread url.
     *
     * @return string
     */
    protected function threadsUrl(): string
    {
        return "{$this->baseUrl}/{$this->category}/{$this->board}/subject.txt";
    }

    /**
     * build post url.
     *
     * @return string
     */
    protected function postUrl(): string
    {
        return "{$this->baseUrl}/bbs/write.cgi";
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

    /**
     * @param $html
     *
     * @return bool
     */
    private function isError($html): bool
    {
        return strpos($html, '<!-- 2ch_X:error -->') !== false;
    }
}
