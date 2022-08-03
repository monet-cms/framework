<?php

namespace Monet\Framework\Module\Repository;

use Monet\Framework\Module\Module;

interface ModuleRepositoryInterface
{
    public function all(): array;

    public function has(string $name): bool;

    public function get(string $name): ?Module;

    public function boot(): void;

    public function validate(string|Module $module): bool;

    public function enable(Module $theme): void;

    public function deactivate(): void;

    public function active(): ?Module;

    public function cache(): void;

    public function clearCache(): void;
}
