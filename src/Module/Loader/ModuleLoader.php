<?php

namespace Monet\Framework\Module\Loader;

use Monet\Framework\Module\Module;
use Monet\Framework\Support\Json;

class ModuleLoader implements ModuleLoaderInterface
{
    public function fromPath(string $path): Module
    {
        $jsonPath = realpath($path . DIRECTORY_SEPARATOR . 'composer.json');

        $json = new Json($jsonPath);

        return new Module(
            $json->get('name'),
            $json->get('description'),
            $json->get('version'),
            $path,
            false,
            $json->get('extra.monet.theme.dependencies', []),
            $json->get('extra.monet.theme.providers', [])
        );
    }

    public function fromCache(array $cache): Module
    {
        return new Module(
            $cache['name'],
            $cache['description'],
            $cache['version'],
            $cache['path'],
            $cache['status'],
            $cache['dependencies'],
            $cache['providers']
        );
    }
}
