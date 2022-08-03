<?php

namespace Monet\Framework;

use Illuminate\Support\AggregateServiceProvider;
use Monet\Framework\Module\ModuleServiceProvider;
use Monet\Framework\Setting\SettingsServiceProvider;
use Monet\Framework\Theme\ThemeServiceProvider;

class MonetServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        SettingsServiceProvider::class,
        ModuleServiceProvider::class,
        ThemeServiceProvider::class,
    ];

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/monet.php', 'monet');

        parent::register();
    }
}
