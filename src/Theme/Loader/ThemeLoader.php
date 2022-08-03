<?php

namespace Monet\Framework\Theme\Loader;

use Monet\Framework\Support\Json;
use Monet\Framework\Theme\Theme;

class ThemeLoader implements ThemeLoaderInterface
{
    public function fromPath(string $path): Theme
    {
        $jsonPath = realpath($path . DIRECTORY_SEPARATOR . 'theme.json');

        $json = new Json($jsonPath);

        return new Theme(
            $json->get('name'),
            $json->get('description'),
            $path,
            $json->get('parent')
        );
    }

    public function fromCache(array $cache): Theme
    {
        return new Theme(
            $cache['name'],
            $cache['description'],
            $cache['path'],
            $cache['parent']
        );
    }
}
