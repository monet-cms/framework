<?php

namespace Monet\Framework\Theme\Listeners;

use Monet\Framework\Theme\Events\ThemeNotFound;
use Monet\Framework\Theme\Repository\ThemeRepositoryInterface;
use Monet\Framework\Theme\Theme;

class ThemeNotFoundListener
{
    protected ThemeRepositoryInterface $themes;

    public function __construct(ThemeRepositoryInterface $themes)
    {
        $this->themes = $themes;
    }

    public function handle(ThemeNotFound $event): void
    {
        $fallbackTheme = collect($this->themes->all())
            ->first(fn(Theme $theme) => $theme->getName() !== $event->theme);

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
                'The theme "%s" cannot be found and has been disabled.',
                $event->theme
            )
        );
    }
}
