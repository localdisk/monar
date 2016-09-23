<?php

namespace Localdisk\Monar;

use Illuminate\Support\ServiceProvider;

class MonarProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind(Factory::class, function () {
            return new MonarManager($this->app);
        });

        $this->app->alias(Factory::class, 'monar');
    }
}
