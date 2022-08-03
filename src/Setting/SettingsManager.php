<?php

namespace Monet\Framework\Setting;

use Illuminate\Support\Manager;
use Monet\Framework\Setting\Drivers\SettingsDatabaseDriver;
use Monet\Framework\Setting\Drivers\SettingsDriverInterface;
use Monet\Framework\Setting\Drivers\SettingsFileDriver;

class SettingsManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('monet.settings.driver');
    }

    public function createFileDriver(): SettingsDriverInterface
    {
        return $this->container->make(SettingsFileDriver::class);
    }

    public function createDatabaseDriver(): SettingsDriverInterface
    {
        return $this->container->make(SettingsDatabaseDriver::class);
    }
}
