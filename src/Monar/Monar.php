<?php

namespace Localdisk\Monar;

use Illuminate\Support\Facades\Facade;

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
