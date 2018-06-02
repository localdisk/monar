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
     * @return string
     * @throws MonarException
     */
    public function post(string $name = '', string $email = '', ?string $text = null): string;


    /**
     * @return string
     */
    public function getCategory(): string;

    /**
     * @return string
     */
    public function getBoard(): string;

    /**
     * @return string
     */
    public function getThread(): string;

}
