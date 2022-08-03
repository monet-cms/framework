<?php

namespace Monet\Framework\Theme;

use Illuminate\Support\ServiceProvider;
use Monet\Framework\Theme\Loader\ThemeLoader;
use Monet\Framework\Theme\Loader\ThemeLoaderInterface;
use Monet\Framework\Theme\Repository\ThemeRepository;
use Monet\Framework\Theme\Repository\ThemeRepositoryInterface;

class ThemeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(
            ThemeLoaderInterface::class,
            ThemeLoader::class
        );

        $this->app->alias(
            ThemeRepositoryInterface::class,
            'monet.theme'
        );
        $this->app->singleton(
            ThemeRepositoryInterface::class,
            ThemeRepository::class
        );
    }

    public function boot()
    {
        $themes = $this->app->make('monet.theme');

        $themes->boot();

        $activeTheme = setting()->get('monet.active-theme');
        if ($activeTheme !== null) {
            $themes->activate($activeTheme);
        }
    }
}
