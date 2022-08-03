<?php

namespace Monet\Framework\Theme;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class Theme implements Arrayable
{
    private string $name;

    private string $description;

    private string $path;

    private ?string $parent;

    public function __construct(
        string $name,
        string $description,
        string $path,
        ?string $parent = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->path = $path;
        $this->parent = $parent;
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

        return realpath($this->path.DIRECTORY_SEPARATOR.$path);
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function getAssetPath(): string
    {
        return Str::slug($this->getName());
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
