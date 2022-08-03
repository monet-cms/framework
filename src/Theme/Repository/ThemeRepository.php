<?php

namespace Monet\Framework\Theme\Repository;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\ViewFinderInterface;
use Monet\Framework\Theme\Exception\ThemeNotFoundException;
use Monet\Framework\Theme\Loader\ThemeLoaderInterface;
use Monet\Framework\Theme\Theme;

class ThemeRepository implements ThemeRepositoryInterface
{
    protected ThemeLoaderInterface $loader;

    protected ViewFinderInterface $view;

    protected array $themes = [];

    protected ?Theme $activeTheme = null;

    public function __construct(
        ThemeLoaderInterface $loader,
        ViewFinderInterface $view
    ) {
        $this->loader = $loader;
        $this->view = $view;
    }

    public function boot(): void
    {
        if ($this->loadCache()) {
            return;
        }

        $this->load();
    }

    public function register(Theme $theme, bool $activate = false): void
    {
        $name = $theme->getName();
        if (! $this->has($name)) {
            $this->themes[$name] = $theme;
        }

        if ($activate) {
            $this->activate($theme);
        }
    }

    public function registerPath(string $path, bool $activate = false): void
    {
        $path = realpath($path);
        $theme = $this->loader->fromPath($path);

        $this->register($theme, $activate);
    }

    public function registerPaths(array $paths, bool $activate = false): void
    {
        foreach ($paths as $path) {
            $this->registerPath($path, $activate);
        }
    }

    public function activate(Theme|string $theme): void
    {
        if (! ($theme instanceof Theme)) {
            $theme = $this->get($theme);
        }

        $this->activeTheme = $theme;

        $this->activateFinderPaths($theme);
    }

    public function all(): array
    {
        return $this->themes;
    }

    public function has(string $name): bool
    {
        return isset($this->themes[$name]);
    }

    public function get(string $name): Theme
    {
        if (! $this->has($name)) {
            throw new ThemeNotFoundException($name);
        }

        return $this->themes[$name];
    }

    public function current(): ?Theme
    {
        return $this->activeTheme;
    }

    public function discover(string $path): array
    {
        $search = realpath($path.'/theme.json');

        return str_replace('theme.json', '', $this->getFiles($search));
    }

    public function cache(): void
    {
        $json = [];
        foreach ($this->themes as $name => $theme) {
            $json[$name] = $theme->toArray();
        }

        Cache::forever($this->getCacheKey(), $json);
    }

    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
    }

    protected function load(): void
    {
        $paths = config('monet.themes.paths');

        foreach ($paths as $path) {
            $files = $this->discover($path);
            $this->registerPaths($files);
        }
    }

    protected function loadCache(): bool
    {
        if (! config('monet.themes.cache.enabled')) {
            return false;
        }

        $key = $this->getCacheKey();

        if (! Cache::has($key)) {
            return false;
        }

        $themes = Cache::get($key);

        foreach ($themes as $name => $theme) {
            $this->themes[$name] = $this->loader->fromCache($theme);
        }

        return true;
    }

    protected function getFiles(string $pattern, int $flags = 0): array
    {
        $files = glob($pattern, $flags);

        if ($files) {
            return $files;
        }

        $files = [];

        $directories = glob(
            dirname($pattern).DIRECTORY_SEPARATOR.'*',
            GLOB_ONLYDIR | GLOB_NOSORT
        );

        if (! $directories) {
            $directories = [];
        }

        foreach ($directories as $directory) {
            $files = array_merge(
                $files,
                $this->getFiles(
                    $directory.DIRECTORY_SEPARATOR.basename($pattern),
                    $flags
                )
            );
        }

        return $files;
    }

    protected function activateFinderPaths(Theme $theme): void
    {
        if ($theme->hasParent()) {
            $this->activateFinderPaths($this->get($theme->getParent()));
        }

        $this->view->getFinder()->prependLocation($theme->getPath('views'));
    }

    protected function getCacheKey(): string
    {
        return config('monet.themes.cache.key');
    }
}