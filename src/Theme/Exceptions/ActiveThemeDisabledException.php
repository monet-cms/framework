<?php

namespace Monet\Framework\Theme\Exceptions;

use Exception;
use Monet\Framework\Theme\Theme;

class ActiveThemeDisabledException extends Exception
{
    public function __construct(Theme $theme)
    {
        parent::__construct(
            sprintf(
                'The currently enabled theme "%s" has errors and has been disabled.',
                $theme->getName()
            )
        );
    }
}
