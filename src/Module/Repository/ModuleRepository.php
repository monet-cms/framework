<?php

namespace Monet\Framework\Module\Repository;

use Illuminate\Support\Facades\Cache;
use Monet\Framework\Module\Loader\ModuleLoaderInterface;
use Monet\Framework\Module\Module;
use Monet\Framework\Setting\SettingsManager;

class ModuleRepository
{
    protected ModuleLoaderInterface $loader;

    protected SettingsManager $settings;

    protected ?array $modules = null;

    protected ?array $orderedModules = null;

    public function __construct(
        ModuleLoaderInterface $loader,
        SettingsManager       $settings
    )
    {
        $this->loader = $loader;
        $this->settings = $settings;
    }

    public function all(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        if (!($modules = $this->loadCache())) {
            $modules = $this->load();
            $this->cache();
        }

        return $this->modules = $modules;
    }

    public function cache(): void
    {
        if (!config('monet.modules.cache.enabled')) {
            return;
        }

        $allCacheKey = config('monet.modules.cache.keys.all');
        Cache::forever(
            $allCacheKey,
            collect($this->modules)
                ->mapWithKeys(fn(Module $module) => [
                    $module->getName() => $module->toArray()
                ])
                ->all()
        );
    }

    protected function loadCache(): ?array
    {
        if (!config('monet.modules.cache.enabled')) {
            return null;
        }

        $cacheKey = config('monet.modules.cache.keys.all');

        if (!Cache::has($cacheKey)) {
            return null;
        }

        return Cache::get($cacheKey, []);
    }

    protected function load(): array
    {
        $paths = config('monet.modules.paths');

        $modules = [];

        $statuses = $this->settings->get('monet.modules.all', []);

        foreach ($paths as $path) {
            $files = $this->discover($path);

            foreach ($files as $file) {
                $module = $this->loader->fromPath($file);

                $name = $module->getName();

                $module->setStatus($statuses[$name] ?? false);

                $modules[$name] = $module;
            }
        }

        $this->settings->set(
            'monet.modules.all',
            collect($this->modules)
                ->mapWithKeys(fn(Module $module) => [
                    $module->getName() => $module->getStatus()
                ])
        );

        return $modules;
    }

    protected function discover(string $path): array
    {
        $search = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'composer.json';

        return str_replace('composer.json', '', $this->getFiles($search));
    }

    protected function getFiles(string $pattern, int $flags = 0): array
    {
        $files = glob($pattern, $flags);

        if ($files) {
            return $files;
        }

        $files = [];

        $directories = glob(
            dirname($pattern) . DIRECTORY_SEPARATOR . '*',
            GLOB_ONLYDIR | GLOB_NOSORT
        );

        if (!$directories) {
            $directories = [];
        }

        foreach ($directories as $directory) {
            $files = array_merge(
                $files,
                $this->getFiles(
                    $directory . DIRECTORY_SEPARATOR . basename($pattern),
                    $flags
                )
            );
        }

        return $files;
    }
}
