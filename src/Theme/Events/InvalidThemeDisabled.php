<?php

namespace Monet\Framework\Theme\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Monet\Framework\Theme\Theme;

class InvalidThemeDisabled
{
    use Dispatchable, SerializesModels;

    public Theme $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }
}
