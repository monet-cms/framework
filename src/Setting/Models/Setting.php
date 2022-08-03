<?php

namespace Monet\Framework\Setting\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public function getTable(): string
    {
        return config('monet.settings.database.table', parent::getTable());
    }

    public function getFillable(): array
    {
        return [
            config('monet.settings.database.columns.key'),
            config('monet.settings.database.columns.value'),
        ];
    }
}
