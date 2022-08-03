<?php

namespace Monet\Framework\Setting\Drivers;

interface SettingsDriverInterface
{
    public function get(string $key, $default = null);

    public function set(string $key, $value): void;

    public function save(): void;

    public function forget(string $key): void;
}
