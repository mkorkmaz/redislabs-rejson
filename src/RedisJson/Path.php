<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson;

final class Path
{
    private $path;

    public function __construct(?string $path = '.')
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
