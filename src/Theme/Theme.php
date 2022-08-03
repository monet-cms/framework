<?php

namespace Monet\Framework\Theme;

use Illuminate\Contracts\Support\Arrayable;

class Theme implements Arrayable
{
    private string $name;

    private string $description;

    private string $path;

    private ?string $parent;

    private array $providers = [];

    public function __construct(
        string  $name,
        string  $description,
        string  $path,
        ?string $parent = null,
        array   $providers = []
    )
    {
        $this->name = $name;
        $this->description = $description;
        $this->path = $path;
        $this->parent = $parent;
        $this->providers = $providers;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPath(?string $path = null): string
    {
        if ($path === null) {
            return $this->path;
        }

        return realpath($this->path . DIRECTORY_SEPARATOR . ltrim($path, '/\\'));
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'path' => $this->getPath(),
            'parent' => $this->getParent(),
        ];
    }
}
