<?php

namespace Monet\Framework\Setting\Facades;

use Illuminate\Support\Facades\Facade;

class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'monet.settings';
    }
}
