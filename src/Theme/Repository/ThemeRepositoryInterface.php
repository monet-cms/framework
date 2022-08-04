<?php

namespace Monet\Framework\Theme\Repository;

use Monet\Framework\Theme\Theme;

interface ThemeRepositoryInterface
{
    public function boot(): void;

    public function all(): array;

    public function has(string $name): bool;

    public function get(string $name): ?Theme;

    public function validate(string|Theme $theme): bool;

    public function activate(Theme $theme): void;

    public function deactivate(): void;

    public function active(): ?Theme;

    public function cache(): void;

    public function clearCache(): void;
}
