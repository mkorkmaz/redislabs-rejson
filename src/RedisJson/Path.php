<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson;

final class Path
{
    private $path;
    private $isLegacyPath;

    public function __construct(?string $path = '.')
    {
        $isLegacyPath = true;
        if ($path[0] === '$') {
            $isLegacyPath = false;
        }
        $this->isLegacyPath = $isLegacyPath;
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isLegacyPath(): bool
    {
        return $this->isLegacyPath;
    }
}
