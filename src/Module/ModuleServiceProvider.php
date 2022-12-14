<?php

namespace Monet\Framework\Module;

use Illuminate\Support\ServiceProvider;
use Monet\Framework\Module\Loader\ModuleLoader;
use Monet\Framework\Module\Loader\ModuleLoaderInterface;
use Monet\Framework\Module\Repository\ModuleRepository;
use Monet\Framework\Module\Repository\ModuleRepositoryInterface;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ModuleLoaderInterface::class,
            ModuleLoader::class
        );

        $this->app->alias(
            ModuleRepositoryInterface::class,
            'monet.modules'
        );
        $this->app->singleton(
            ModuleRepositoryInterface::class,
            ModuleRepository::class
        );
    }

    public function boot(): void
    {
        $themes = $this->app->make('monet.modules');

        $themes->boot();
    }
}
