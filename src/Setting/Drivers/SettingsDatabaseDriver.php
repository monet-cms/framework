<?php

namespace Monet\Framework\Setting\Drivers;

use Monet\Framework\Setting\Models\Setting;

class SettingsDatabaseDriver extends SettingsDriverBase
{
    protected string $keyColumn;

    protected string $valueColumn;

    public function __construct(
        bool $cacheEnabled,
        string $cacheKey,
        int $cacheTtl,
        string $keyColumn,
        string $valueColumn
    ) {
        parent::__construct($cacheEnabled, $cacheKey, $cacheTtl);

        $this->keyColumn = $keyColumn;
        $this->valueColumn = $valueColumn;
    }

    public function save(): void
    {
        if (! empty($this->updated)) {
            Setting::query()
                ->upsert(
                    collect($this->updated)->map(fn (string $key) => [
                        $this->keyColumn => $key,
                        $this->valueColumn => $this->encode($this->get($key)),
                    ])->toArray(),
                    $this->keyColumn
                );

            foreach ($this->updated as $key) {
                $this->setCache($key, $this->get($key));
            }
        }

        if (! empty($this->deleted)) {
            Setting::query()
                ->whereIn($this->keyColumn, '=', $this->deleted)
                ->delete();

            foreach ($this->deleted as $key) {
                $this->clearCache($key);
            }
        }
    }

    protected function load(string $key, $default = null)
    {
        $value = Setting::query()
            ->where($this->keyColumn, '=', $key)
            ->limit(1)
            ->first();

        if ($value !== null) {
            return $this->decode($value);
        }

        return $default;
    }
}
