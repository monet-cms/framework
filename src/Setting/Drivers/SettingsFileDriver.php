<?php

namespace Monet\Framework\Setting\Drivers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;

class SettingsFileDriver extends SettingsDriverBase
{
    protected string $path;

    protected Filesystem $files;

    protected bool $booted = false;

    public function __construct(
        bool $cacheEnabled,
        string $cacheKey,
        int $cacheTtl,
        string $path,
        Filesystem $files
    ) {
        parent::__construct($cacheEnabled, $cacheKey, $cacheTtl);

        $this->path = $path;
        $this->files = $files;
    }

    public function save(): void
    {
        if (empty($this->updated) && empty($this->deleted)) {
            return;
        }

        $this->files->put($this->path, $this->encode($this->data));
    }

    protected function boot(string $key, $default = null): void
    {
        if ($this->booted) {
            return;
        }

        if (($value = $this->loadCache($key, $default)) === null) {
            $value = $this->load($key, $default);
        }

        $this->data = $value;
    }

    protected function loadCache(string $key, $default = null)
    {
        if (! $this->cacheEnabled) {
            return null;
        }

        $cacheKey = $this->getCacheKey($key);

        if (! Cache::has($cacheKey)) {
            return null;
        }

        return Cache::get($cacheKey, []);
    }

    protected function load(string $key, $default = null)
    {
        if (! $this->files->exists($this->path)) {
            return [];
        }

        return $this->decode($this->files->get($this->path));
    }
}
