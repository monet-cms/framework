<?php

namespace Monet\Framework\Theme\Listeners;

use Monet\Framework\Theme\Events\InvalidThemeDisabled;
use Monet\Framework\Theme\Repository\ThemeRepositoryInterface;
use Monet\Framework\Theme\Theme;

class InvalidThemeDisabledListener
{
    protected ThemeRepositoryInterface $themes;

    public function __construct(ThemeRepositoryInterface $themes)
    {
        $this->themes = $themes;
    }

    public function handle(InvalidThemeDisabled $event): void
    {
        $fallbackTheme = collect($this->themes->all())
            ->first(fn (Theme $theme) => $theme->getName() !== $event->theme->getName());

        if (
            $fallbackTheme !== null &&
            $this->themes->validate($fallbackTheme)
        ) {
            $this->themes->activate($fallbackTheme);
        }

        $this->themes->clearCache();

        session()->flash(
            'theme.error',
            sprintf(
                'The theme "%s" has errors and has been disabled.',
                $event->theme->getName()
            )
        );
    }
}
