<?php

namespace Monet\Framework\Setting;

use Illuminate\Support\ServiceProvider;
use Monet\Framework\Setting\Console\Commands\SettingsTableCommand;
use Monet\Framework\Setting\Drivers\SettingsDatabaseDriver;
use Monet\Framework\Setting\Drivers\SettingsFileDriver;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerManager();
        $this->registerDrivers();
        $this->registerSettingsTableCommand();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SettingsTableCommand::class,
            ]);
        }
    }

    protected function registerManager(): void
    {
        $this->app->alias(SettingsManager::class, 'monet.settings');
        $this->app->singleton(SettingsManager::class);

        $this->app->terminating(function () {
            $this->app->make('monet.settings')->save();
        });
    }

    protected function registerDrivers(): void
    {
        $this->registerFileDriver();
        $this->registerDatabaseDriver();
    }

    protected function registerSettingsTableCommand(): void
    {
        $this->app->singleton(SettingsTableCommand::class, function ($app) {
            return new SettingsTableCommand($app['files'], $app['composer']);
        });
    }

    protected function registerFileDriver()
    {
        $this->app->singleton(SettingsFileDriver::class);

        $this->registerBaseParameters(SettingsFileDriver::class);

        $this->app->when(SettingsFileDriver::class)
            ->needs('$path')
            ->giveConfig('monet.settings.file.path');
    }

    protected function registerDatabaseDriver()
    {
        $this->app->singleton(SettingsDatabaseDriver::class);

        $this->registerBaseParameters(SettingsDatabaseDriver::class);

        $this->app->when(SettingsDatabaseDriver::class)
            ->needs('$keyColumn')
            ->giveConfig('monet.settings.database.columns.key');

        $this->app->when(SettingsDatabaseDriver::class)
            ->needs('$valueColumn')
            ->giveConfig('monet.settings.database.columns.value');
    }

    protected function registerBaseParameters(string $class)
    {
        $this->app->when($class)
            ->needs('$cacheEnabled')
            ->giveConfig('monet.settings.cache.enabled');

        $this->app->when($class)
            ->needs('$cacheKey')
            ->giveConfig('monet.settings.cache.key');

        $this->app->when($class)
            ->needs('$cacheTtl')
            ->giveConfig('monet.settings.cache.ttl');
    }
}
