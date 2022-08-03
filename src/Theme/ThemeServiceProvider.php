<?php

namespace Monet\Framework\Theme;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Monet\Framework\Theme\Events\InvalidThemeDisabled;
use Monet\Framework\Theme\Listeners\InvalidThemeDisabledListener;
use Monet\Framework\Theme\Loader\ThemeLoader;
use Monet\Framework\Theme\Loader\ThemeLoaderInterface;
use Monet\Framework\Theme\Repository\ThemeRepository;
use Monet\Framework\Theme\Repository\ThemeRepositoryInterface;

class ThemeServiceProvider extends EventServiceProvider
{
    protected $listen = [
        InvalidThemeDisabled::class => [
            InvalidThemeDisabledListener::class,
        ],
    ];

    public function register()
    {
        parent::register();

        $this->app->singleton(
            ThemeLoaderInterface::class,
            ThemeLoader::class
        );

        $this->app->alias(
            ThemeRepositoryInterface::class,
            'monet.themes'
        );
        $this->app->singleton(
            ThemeRepositoryInterface::class,
            ThemeRepository::class
        );
    }

    public function boot()
    {
        $themes = $this->app->make('monet.themes');

        $themes->boot();

        $activeTheme = settings('monet.active-theme');
        if (
            $activeTheme !== null &&
            $theme = $themes->get($activeTheme)
        ) {
            $themes->activate($theme);
        }
    }
}
