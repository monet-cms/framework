<?php

namespace Monet\Framework\Module\Repository;

use Monet\Framework\Module\Module;

interface ModuleRepositoryInterface
{
    public function boot(): void;

    public function all(): array;

    public function ordered(): array;

    public function has(string $name): bool;

    public function enabled(): array;

    public function disabled(): array;

    public function status(string $status): array;

    public function enable(Module|string $module): void;

    public function disable(Module|string $module): void;

    public function setStatus(Module|string $module, string $status): void;

    public function get(string $name): ?Module;

    public function validate(string|Module $module): bool;

    public function cache(): void;

    public function clearCache(): void;
}
