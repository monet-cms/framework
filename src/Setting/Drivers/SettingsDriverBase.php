<?php

namespace Monet\Framework\Setting\Drivers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

abstract class SettingsDriverBase implements SettingsDriverInterface
{
    protected bool $cacheEnabled;

    protected string $cacheKey;

    protected int $cacheTtl;

    protected array $data = [];

    protected array $updated = [];

    protected array $deleted = [];

    public function __construct(
        bool $cacheEnabled,
        string $cacheKey,
        int $cacheTtl
    ) {
        $this->cacheEnabled = $cacheEnabled;
        $this->cacheKey = $cacheKey;
        $this->cacheTtl = $cacheTtl;
    }

    public function get(string $key, $default = null)
    {
        if (! Arr::has($this->data, $key)) {
            $this->boot($key, $default);
        }

        return Arr::get($this->data, $key, $default);
    }

    public function set(string $key, $value): void
    {
        Arr::set($this->data, $key, $value);

        $this->updated[] = $key;
    }

    public function forget(string $key): void
    {
        Arr::forget($this->data, $key);

        $this->deleted[] = $key;
    }

    abstract public function save(): void;

    abstract protected function load(string $key, $default = null);

    protected function boot(string $key, $default = null): void
    {
        if (($value = $this->loadCache($key, $default)) === null) {
            $value = $this->load($key, $default);
        }

        Arr::set($this->data, $key, $value);
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

        return Cache::get($cacheKey, $default);
    }

    protected function setCache(string $key, $value)
    {
        if (! $this->cacheEnabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($key);

        $ttl = $this->cacheTtl;
        Cache::put($cacheKey, $value, $ttl === -1 ? null : $ttl);
    }

    protected function clearCache(string $key): void
    {
        if (! $this->cacheEnabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($key);

        Cache::forget($cacheKey);
    }

    protected function decode(string $value)
    {
        return json_decode($value, true);
    }

    protected function encode($value): string
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        return json_encode($value);
    }

    protected function getCacheKey(string $key): string
    {
        return $this->cacheKey.'.'.$key;
    }
}
