<?php

namespace Localdisk\Monar;

use Illuminate\Support\Collection;
use Localdisk\Monar\Exceptions\MonarException;

interface Driver
{
    /**
     * get threads.
     *
     * @return \Illuminate\Support\Collection
     * @throws MonarException
     */
    public function threads(): Collection;

    /**
     * get messages.
     *
     * @param int|null $start
     * @param int|null $end
     *
     * @return \Illuminate\Support\Collection
     */
    public function messages(?int $start = null, ?int $end = null): Collection;

    /**
     * post message.
     *
     * @param string $name
     * @param string $email
     * @param string|null $text
     *
     * @return mixed|string
     * @throws MonarException
     */
    public function post(string $name = '', string $email = 'sage', ?string $text = null);
}
