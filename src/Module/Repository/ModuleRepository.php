<?php

namespace Monet\Framework\Module\Repository;

use Illuminate\Support\Facades\Cache;
use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\Implementations\FixedArraySort;
use Monet\Framework\Module\Loader\ModuleLoaderInterface;
use Monet\Framework\Module\Module;
use Monet\Framework\Setting\SettingsManager;

class ModuleRepository implements ModuleRepositoryInterface
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
        }

        return $this->modules = $modules;
    }

    public function ordered(): array
    {
        if ($this->orderedModules !== null) {
            return $this->orderedModules;
        }

        if (!($modules = $this->loadOrderedCache())) {
            $modules = $this->loadOrdered();
            $this->cache();
        }

        return $this->orderedModules = $modules;
    }

    public function enabled(): array
    {
        return collect($this->modules)
            ->filter(fn(Module $module) => $module->enabled())
            ->all();
    }

    public function disabled(): array
    {
        return collect($this->modules)
            ->filter(fn(Module $module) => $module->disabled())
            ->all();
    }

    public function status(string $status): array
    {
        return collect($this->modules)
            ->filter(fn(Module $module) => $module->getStatus() === $status)
            ->all();
    }

    public function enable(Module|string $module): void
    {
        $this->setStatus($module, 'enabled');
    }

    public function disable(Module|string $module): void
    {
        $this->setStatus($module, 'disabled');
    }

    public function setStatus(Module|string $module, string $status): void
    {
        if (!($module instanceof Module)) {
            $module = $this->get($module);
        }

        if ($module === null) {
            return;
        }

        $module->setStatus($status);

        $this->settings->set(
            'monet.modules.' . $module->getName(),
            $status
        );

        $this->clearCache();
    }

    public function get(string $name): ?Module
    {
        return $this->modules[$name] ?? null;
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

        $orderedCacheKey = config('monet.modules.cache.keys.ordered');
        Cache::forever(
            $orderedCacheKey,
            collect($this->ordered())
                ->map(fn(Module $module) => $module->getName())
                ->all()
        );
    }

    public function clearCache(): void
    {
        if (!config('monet.modules.cache.enabled')) {
            return;
        }

        Cache::forget(config('monet.modules.cache.keys.all'));
        Cache::forget(config('monet.modules.cache.keys.ordered'));
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

        $statuses = $this->settings->get('monet.modules', []);

        foreach ($paths as $path) {
            $files = $this->discover($path);

            foreach ($files as $file) {
                $module = $this->loader->fromPath($file);

                $name = $module->getName();

                if (isset($statuses[$name])) {
                    $module->setStatus($statuses[$name]);
                }

                $modules[$name] = $module;
            }
        }

        return $modules;
    }

    protected function loadOrderedCache(): ?array
    {
        if (!config('monet.modules.cache.enabled')) {
            return null;
        }

        $cacheKey = config('monet.modules.cache.keys.ordered');

        if (!Cache::has($cacheKey)) {
            return null;
        }

        $modules = [];

        $names = Cache::get($cacheKey, []);
        foreach ($names as $name) {
            if ($module = $this->get($name)) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    protected function loadOrdered(): array
    {
        $modules = [];

        $names = $this->getOrderedNames();
        foreach ($names as $name) {
            if ($module = $this->get($name)) {
                $modules[] = $module;
            }
        }

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

    protected function getOrderedNames(): array
    {
        $sorter = new FixedArraySort();

        $modules = collect($this->enabled());

        $modules->each(function ($module, $name) use ($sorter) {
            $sorter->add($name, $module->get('dependencies', []));
        });

        $names = [];

        $maxAttempts = $modules->count();

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $names = $sorter->sort();
                break;
            } catch (CircularDependencyException $e) {
                $this->disable($e->getNodes());
            } catch (ElementNotFoundException $e) {
                $this->disable($e->getSource());
            }
        }

        return $names;
    }
}
