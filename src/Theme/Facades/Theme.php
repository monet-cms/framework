<?php

namespace Monet\Framework\Theme\Facades;

use Illuminate\Support\Facades\Facade;

class Theme extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'monet.theme';
    }
}