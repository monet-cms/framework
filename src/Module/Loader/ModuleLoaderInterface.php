<?php

namespace Monet\Framework\Module\Loader;

use Monet\Framework\Module\Module;

interface ModuleLoaderInterface
{
    public function fromPath(string $path): Module;

    public function fromCache(array $cache): Module;
}
