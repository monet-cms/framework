<?php

namespace Monet\Framework\Theme\Exceptions;

use Exception;

class ThemeNotFoundException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct(
            sprintf('Theme "%s" cannot be found.', $name)
        );
    }
}
