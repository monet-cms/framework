<?php

namespace Monet\Framework\Theme\Repository;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Monet\Framework\Theme\Events\InvalidThemeDisabled;
use Monet\Framework\Theme\Exceptions\ActiveThemeDisabledException;
use Monet\Framework\Theme\Loader\ThemeLoaderInterface;
use Monet\Framework\Theme\Theme;

class ThemeRepository implements ThemeRepositoryInterface
{
    protected ThemeLoaderInterface $loader;

    protected Factory $view;

    protected array $themes = [];

    protected ?Theme $activeTheme = null;

    public function __construct(
        ThemeLoaderInterface $loader,
        Factory              $view
    )
    {
        $this->loader = $loader;
        $this->view = $view;
    }

    public function boot(): void
    {
        if ($this->loadCache()) {
            return;
        }

        $this->load();
        $this->cache();
    }

    public function register(Theme $theme, bool $activate = false): void
    {
        $name = $theme->getName();
        if (!$this->has($name)) {
            $this->themes[$name] = $theme;
        }

        if ($activate) {
            $this->activate($theme);
        }
    }

    public function registerPath(string $path, bool $activate = false): void
    {
        $path = realpath($path);
        if (!$path) {
            return;
        }

        $theme = $this->loader->fromPath($path);

        $this->register($theme, $activate);
    }

    public function registerPaths(array $paths, bool $activate = false): void
    {
        foreach ($paths as $path) {
            $this->registerPath($path, $activate);
        }
    }

    public function validate(Theme|string $theme): bool
    {
        if (!($theme instanceof Theme)) {
            $theme = $this->get($theme);
        }

        if ($theme === null) {
            return false;
        }

        if (!File::exists($theme->getPath('composer.json'))) {
            return false;
        }

        if ($parent = $theme->getParent()) {
            $parentTheme = $this->get($parent);

            return $this->validate($parentTheme);
        }

        return true;
    }

    public function activate(Theme|string $theme): void
    {
        if (!($theme instanceof Theme)) {
            $theme = $this->get($theme);
        }

        if (!$this->validate($theme)) {
            InvalidThemeDisabled::dispatch($theme);
            return;
        }

        $this->activeTheme = $theme;

        $this->activateFinderPaths($theme);

        $this->registerProviders($theme);
    }

    public function deactivate(): void
    {
        $this->activeTheme = null;
        $this->clearCache();
    }

    public function all(): array
    {
        return $this->themes;
    }

    public function has(string $name): bool
    {
        return isset($this->themes[$name]);
    }

    public function get(string $name): ?Theme
    {
        return $this->themes[$name] ?? null;
    }

    public function active(): ?Theme
    {
        return $this->activeTheme;
    }

    public function discover(string $path): array
    {
        $search = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'composer.json';

        return str_replace('composer.json', '', $this->getFiles($search));
    }

    public function cache(): void
    {
        $json = [];
        foreach ($this->themes as $theme) {
            $json[] = $theme->toArray();
        }

        Cache::forever($this->getCacheKey(), $json);
    }

    public function clearCache(): void
    {
        if (!config('monet.themes.cache.enabled')) {
            return;
        }

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
        if (!config('monet.themes.cache.enabled')) {
            return false;
        }

        $key = $this->getCacheKey();

        if (!Cache::has($key)) {
            return false;
        }

        $themes = Cache::get($key);

        foreach ($themes as $theme) {
            $this->register($this->loader->fromCache($theme));
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

    protected function activateFinderPaths(Theme $theme): void
    {
        if ($theme->hasParent()) {
            $this->activateFinderPaths($this->get($theme->getParent()));
        }

        $this->view->getFinder()->prependLocation($theme->getPath('resources/views'));
    }

    protected function registerProviders(Theme $theme): void
    {
        if (empty($theme->getProviders())) {
            return;
        }

        (new ProviderRepository(
            app(),
            app('filesystem'),
            storage_path(Str::snake($theme->getName()) . '_theme.php')
        ))->load($theme->getProviders());
    }

    protected function getCacheKey(): string
    {
        return config('monet.themes.cache.key');
    }
}
