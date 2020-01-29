<?php

namespace Localdisk\Monar;

use Illuminate\Support\Facades\Facade;

/**
 * Class Monar
 * @package Localdisk\Monar
 * @method Driver bbs(string|null $url)
 */
class Monar extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'monar';
    }
}
