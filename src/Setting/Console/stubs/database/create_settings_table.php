<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = $this->getTable();
        $keyColumn = $this->getKeyColumn();
        $valueColumn = $this->getValueColumn();

        Schema::create($table, function (Blueprint $table) use ($keyColumn, $valueColumn) {
            $table->id();
            $table->string($keyColumn)->unique();
            $table->json($valueColumn);
        });
    }

    public function down(): void
    {
        $table = $this->getTable();

        Schema::dropIfExists($table);
    }

    protected function getTable(): string
    {
        return config('monet.settings.database.table');
    }

    protected function getKeyColumn(): string
    {
        return config('monet.settings.database.columns.key');
    }

    protected function getValueColumn(): string
    {
        return config('monet.settings.database.columns.value');
    }
};
