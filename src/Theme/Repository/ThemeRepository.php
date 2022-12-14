<?php

namespace Monet\Framework\Theme\Repository;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Monet\Framework\Theme\Events\InvalidThemeDisabled;
use Monet\Framework\Theme\Loader\ThemeLoaderInterface;
use Monet\Framework\Theme\Theme;

class ThemeRepository implements ThemeRepositoryInterface
{
    protected ThemeLoaderInterface $loader;

    protected Factory $view;

    protected array $themes;

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

    protected function getCacheKey(): string
    {
        return config('monet.themes.cache.key');
    }

    public function has(string $name): bool
    {
        return isset($this->themes[$name]);
    }

    public function get(string $name): ?Theme
    {
        return $this->themes[$name] ?? null;
    }

    protected function register(Theme $theme, bool $activate = false): void
    {
        $name = $theme->getName();
        if (!$this->has($name)) {
            $this->themes[$name] = $theme;
        }

        if ($activate) {
            $this->activate($theme);
        }
    }

    public function activate(Theme $theme): void
    {
        if (!$this->validate($theme)) {
            InvalidThemeDisabled::dispatch($theme);

            return;
        }

        $this->activeTheme = $theme;

        $this->activateFinderPaths($theme);

        $this->registerProviders($theme);
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

        if ($theme->hasParent()) {
            $parentTheme = $this->get($theme->getParent());

            return $this->validate($parentTheme);
        }

        return true;
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
            app('files'),
            storage_path(
                Str::snake(str_replace([
                    '/',
                    '\\'
                ], '_', $theme->getName())) .
                '_theme.php'
            )
        ))->load($theme->getProviders());
    }

    protected function load(): void
    {
        $paths = config('monet.themes.paths');

        $this->themes = [];

        foreach ($paths as $path) {
            $files = $this->discover($path);
            $this->registerPaths($files);
        }
    }

    protected function discover(string $path): array
    {
        $search = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'composer.json';

        return str_replace('composer.json', '', File::find($search));
    }

    protected function registerPaths(array $paths, bool $activate = false): void
    {
        foreach ($paths as $path) {
            $this->registerPath($path, $activate);
        }
    }

    protected function registerPath(string $path, bool $activate = false): void
    {
        $path = realpath($path);
        if (!$path) {
            return;
        }

        $theme = $this->loader->fromPath($path);

        $this->register($theme, $activate);
    }

    public function cache(): void
    {
        $json = [];
        foreach ($this->themes as $theme) {
            $json[] = $theme->toArray();
        }

        Cache::forever($this->getCacheKey(), $json);
    }

    public function deactivate(): void
    {
        $this->activeTheme = null;
        $this->clearCache();
    }

    public function clearCache(): void
    {
        if (!config('monet.themes.cache.enabled')) {
            return;
        }

        Cache::forget($this->getCacheKey());
    }

    public function all(): array
    {
        return $this->themes;
    }

    public function active(): ?Theme
    {
        return $this->activeTheme;
    }
}
