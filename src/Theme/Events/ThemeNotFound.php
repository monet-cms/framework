<?php

namespace Monet\Framework\Theme\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ThemeNotFound
{
    use Dispatchable, SerializesModels;

    public string $theme;

    public function __construct(string $theme)
    {
        $this->theme = $theme;
    }
}
