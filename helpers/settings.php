<?php

use Monet\Framework\Setting\SettingsManager;

if (!function_exists('settings')) {
    function settings(): SettingsManager
    {
        return app('monet.settings');
    }
}

if (!function_exists('settings_get')) {
    function settings_get(string $key, $default = null)
    {
        return settings()->get($key, $default);
    }
}

if (!function_exists('settings_set')) {
    function settings_set(string $key, $value): void
    {
        settings()->set($key, $value);
    }
}
