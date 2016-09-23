<?php

namespace Localdisk\Monar;

use GuzzleHttp\Client;

class MonarManager implements Factory
{

    /**
     * @var string
     */
    const SHITARABA = 'http://jbbs.shitaraba.net';

    /**
     * @var string
     */
    protected $url;

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * MonarManager constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @param null $url
     *
     * @return Driver
     */
    public function bbs($url = null)
    {
        if (is_null($url)) {
            throw new \InvalidArgumentException('Drivier is null');
        }

        $client = new Client(['cookies' => true]);
        if ($this->startsWith($url, self::SHITARABA)) {
            return new ShitarabaDriver($url, $client);
        }

        return new TwoChanDriver($url, $client);
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    protected function startsWith($haystack, $needle)
    {
        if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
            return true;
        }

        return false;
    }
}
