<?php

namespace Monet\Framework\Theme\Loader;

use Monet\Framework\Theme\Theme;
use stdClass;

interface ThemeLoaderInterface
{
    public function fromPath(string $path): Theme;

    public function fromCache(stdClass $cache): Theme;
}
