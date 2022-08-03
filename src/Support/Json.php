<?php

namespace Monet\Framework\Support;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class Json implements Arrayable
{
    private string $path;

    private ?array $json = null;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function get(?string $key = null, $default = null)
    {
        if ($key !== null) {
            return Arr::get($this->get(), $key, $default);
        }

        if ($this->json !== null) {
            return $this->json;
        }

        if (! File::exists($this->path)) {
            throw new FileNotFoundException(
                sprintf('Json file path cannot be found at %s', $this->path)
            );
        }

        return $this->json = json_decode(File::get($this->path));
    }

    public function toArray()
    {
        return $this->json;
    }
}
