<?php

namespace Monet\Framework\Theme\Listeners;

use Monet\Framework\Theme\Events\InvalidThemeDisabled;
use Monet\Framework\Theme\Exceptions\ActiveThemeDisabledException;
use Monet\Framework\Theme\Repository\ThemeRepositoryInterface;

class InvalidThemeDisabledListener
{
    protected ThemeRepositoryInterface $theme;

    public function __construct(ThemeRepositoryInterface $themes)
    {
        $this->themes = $themes;
    }

    public function handle(InvalidThemeDisabled $event): void
    {
        $activeTheme = $this->themes->active();
        if ($activeTheme === null) {
            throw new ActiveThemeDisabledException($event->theme);
        }

        session()->flash(
            'theme.error',
            sprintf(
                'The theme "%s" has errors and has been disabled.',
                $event->theme->getName()
            )
        );
    }
}
