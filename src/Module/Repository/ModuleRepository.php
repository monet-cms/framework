<?php

namespace Monet\Framework\Module\Repository;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\Implementations\FixedArraySort;
use Monet\Framework\Module\Loader\ModuleLoaderInterface;
use Monet\Framework\Module\Module;

class ModuleRepository implements ModuleRepositoryInterface
{
    protected ModuleLoaderInterface $loader;

    protected array $modules;

    protected array $ordered;

    public function __construct(ModuleLoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    public function boot(): void
    {
        if ($this->loadCache() && $this->loadOrderedCache()) {
            return;
        }

        $this->load();
        $this->loadOrdered();

        $this->cache();
    }

    protected function loadCache(): bool
    {
        if (!config('monet.modules.cache.enabled')) {
            return false;
        }

        $cacheKey = config('monet.modules.cache.keys.all');

        $modules = Cache::get($cacheKey);
        if ($modules === null) {
            return false;
        }

        $this->modules = [];
        foreach ($modules as $name => $module) {
            $this->modules[$name] = $this->loader->fromCache($module);
        }

        return true;
    }

    public function get(string $name): ?Module
    {
        return $this->all()[$name] ?? null;
    }

    public function all(): array
    {
        return $this->modules;
    }

    protected function loadOrderedCache(): bool
    {
        if (!config('monet.modules.cache.enabled')) {
            return false;
        }

        $cacheKey = config('monet.modules.cache.keys.ordered');

        $modules = Cache::get($cacheKey);
        if ($modules === null) {
            return false;
        }

        $this->ordered = [];
        foreach ($modules as $name) {
            if ($module = $this->get($name)) {
                $this->ordered[] = $module;
            }
        }

        return true;
    }

    protected function load(): void
    {
        $paths = config('monet.modules.paths');

        $this->modules = [];

        foreach ($paths as $path) {
            $files = $this->discover($path);
            $this->registerPaths($files);
        }

        $statuses = settings('monet.modules', []);
        foreach ($statuses as $name => $status) {
            $this->setStatus($name, $status);
        }
    }

    protected function discover(string $path): array
    {
        $search = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'composer.json';

        return str_replace('composer.json', '', File::find($search));
    }

    protected function registerPaths(array $paths): void
    {
        foreach ($paths as $path) {
            $this->registerPath($path);
        }
    }

    protected function registerPath(string $path): void
    {
        $path = realpath($path);
        if (!$path) {
            return;
        }

        $theme = $this->loader->fromPath($path);

        $name = $theme->getName();
        if (!$this->has($name)) {
            $this->modules[$name] = $theme;
        }
    }

    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
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

        settings_set(
            'monet.modules.' . $module->getName(),
            $status
        );

        $this->clearCache();
    }

    public function clearCache(): void
    {
        if (!config('monet.modules.cache.enabled')) {
            return;
        }

        Cache::forget(config('monet.modules.cache.keys.all'));
        Cache::forget(config('monet.modules.cache.keys.ordered'));
    }

    protected function loadOrdered(): void
    {
        $this->ordered = [];

        $names = $this->getOrderedNames();
        foreach ($names as $name) {
            if ($module = $this->get($name)) {
                $this->ordered[$name] = $module;
            }
        }
    }

    protected function getOrderedNames(): array
    {
        $sorter = new FixedArraySort();

        $modules = $this->enabled();
        foreach ($modules as $name => $module) {
            $sorter->add($name, $module->getDependencies());
        }

        $names = [];

        $maxAttempts = count($modules);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $names = $sorter->sort();
                break;
            } catch (CircularDependencyException $e) {
                foreach ($e->getNodes() as $name) {
                    $this->disable($name);
                }
            } catch (ElementNotFoundException $e) {
                $this->disable($e->getSource());
            }
        }

        return $names;
    }

    public function enabled(): array
    {
        return collect($this->all())
            ->filter(fn(Module $module) => $module->enabled())
            ->all();
    }

    public function disable(Module|string $module): void
    {
        $this->setStatus($module, 'disabled');
    }

    public function cache(): void
    {
        if (!config('monet.modules.cache.enabled')) {
            return;
        }

        Cache::forever(
            config('monet.modules.cache.keys.all'),
            collect($this->modules)
                ->mapWithKeys(fn(Module $module) => [
                    $module->getName() => $module->toArray()
                ])
                ->all()
        );

        Cache::forever(
            config('monet.modules.cache.keys.ordered'),
            collect($this->ordered)
                ->map(fn(Module $module) => $module->getName())
                ->all()
        );
    }

    public function ordered(): array
    {
        return $this->ordered;
    }

    public function disabled(): array
    {
        return collect($this->all())
            ->where(fn(Module $module) => $module->disabled())
            ->all();
    }

    public function status(string $status): array
    {
        return collect($this->all())
            ->where(fn(Module $module) => $module->getStatus() === $status)
            ->all();
    }

    public function enable(Module|string $module): void
    {
        $this->setStatus($module, 'enabled');
    }

    public function validate(Module|string $module): bool
    {
        if (!($module instanceof Module)) {
            $module = $this->get($module);
        }

        if ($module === null) {
            return false;
        }

        if (!File::exists($module->getPath('composer.json'))) {
            return false;
        }

        foreach ($module->getDependencies() as $name) {
            if (!$this->validate($name)) {
                return false;
            }
        }

        return true;
    }
}
