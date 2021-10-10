<?php

namespace Localdisk\Monar;

interface Factory
{
    /**
     * @param  null  $url
     * @return Driver
     */
    public function bbs($url = null);
}
