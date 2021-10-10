<?php

namespace Localdisk\Monar;

use Illuminate\Support\Collection;
use Localdisk\Monar\Exceptions\MonarException;

class ShitarabaDriver extends AbstractDriver
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://jbbs.shitaraba.net';

    /**
     * @var string
     */
    protected $encoding = 'EUC-JP';

    /**
     * @var string
     */
    protected $resNumber = '';

    /**
     * get threads.
     *
     * @return \Illuminate\Support\Collection
     *
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
     * @param  int|null  $start
     * @param  int|null  $end
     * @return \Illuminate\Support\Collection
     *
     * @throws MonarException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function messages(?int $start = null, ?int $end = null): Collection
    {
        $body = $this->request('GET', $this->messagesUrl());

        return $this->parseDatCollection($body);
    }

    /**
     * post message.
     *
     * @param  string  $name
     * @param  string  $email
     * @param  string|null  $text
     * @return string
     *
     * @throws MonarException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post(?string $name = '', ?string $email = '', ?string $text = null): string
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
            'Referer'        => $this->url.'/',
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
            $this->resNumber = isset($paths[5]) ? $paths[5] : '';
        } else {
            $this->category = $paths[0];
            $this->board = $paths[1];
        }
    }

    /**
     * parse dat collection.
     *
     * @param  string  $body
     * @param  int|null  $start
     * @param  int|null  $end
     * @return \Illuminate\Support\Collection
     */
    protected function parseDatCollection(string $body, ?int $start = null, ?int $end = null): Collection
    {
        $lines = array_filter(explode("\n", $body), '\strlen');

        return collect(array_map(function ($line) {
            [$number, $name, $email, $date, $body, $title, $resid] = explode('<>', $line);
            $name = trim(strip_tags($name));
            $body = strip_tags($body, '<br>');

            return compact('number', 'name', 'email', 'date', 'body', 'title', 'resid');
        }, $lines));
    }

    /**
     * parse threads collection.
     *
     * @param  string  $body
     * @return \Illuminate\Support\Collection
     */
    protected function parseThreadsCollection(string $body): Collection
    {
        $threads = array_filter(explode("\n", $body), '\strlen');

        return collect(array_map(function ($elem) {
            [$id, $tmp] = explode('.cgi,', $elem);
            preg_match('/^(.*)\((\d+)\)\z/', $tmp, $matches);

            return [
                'url'   => vsprintf('https://%s/bbs/read.cgi/%s/%s/%d', [
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
     * @param  int  $start
     * @param  int|null  $end
     * @return string
     */
    protected function messagesUrl(): string
    {
        return "{$this->baseUrl}/bbs/rawmode.cgi/{$this->category}/{$this->board}/{$this->thread}/{$this->resNumber}";
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
     * @param  string  $html
     * @return bool
     */
    private function confirm(string $html): bool
    {
        return strpos($html, '書き込み確認') !== false;
    }

    /**
     * @param  string  $html
     * @return bool
     */
    private function isError(string $html): bool
    {
        return strpos($html, '<!-- 2ch_X:error -->') !== false;
    }
}
