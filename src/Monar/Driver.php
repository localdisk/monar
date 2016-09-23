<?php

namespace Localdisk\Monar;

use Localdisk\Monar\Exceptions\MonarException;

interface Driver
{
    /**
     * get threads.
     *
     * @return \Illuminate\Support\Collection
     * @throws MonarException
     */
    public function threads();

    /**
     * get messages.
     *
     * @param int $start
     * @param int $end
     *
     * @return \Illuminate\Support\Collection
     */
    public function messages($start = 1, $end = null);

    /**
     * post message.
     *
     * @param string $name
     * @param string $email
     * @param null $text
     *
     * @return mixed|string
     * @throws MonarException
     */
    public function post($name = '', $email = 'sage', $text = null);
}
