<?php

namespace Monet\Framework\Theme\Repository;

use Monet\Framework\Theme\Theme;

interface ThemeRepositoryInterface
{
    public function boot(): void;

    public function register(Theme $theme, bool $activate = false): void;

    public function registerPath(string $path, bool $activate = false): void;

    public function registerPaths(array $paths, bool $activate = false): void;

    public function validate(string|Theme $theme): bool;

    public function activate(string|Theme $theme): void;

    public function deactivate(): void;

    public function all(): array;

    public function has(string $name): bool;

    public function get(string $name): Theme;

    public function active(): ?Theme;

    public function discover(string $path): array;

    public function cache(): void;

    public function clearCache(): void;
}
