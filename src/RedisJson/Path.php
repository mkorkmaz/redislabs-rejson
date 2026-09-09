<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson;

final readonly class Path
{
    public function __construct(private ?string $path = '.')
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isLegacyPath(): bool
    {
        return !str_starts_with($this->path ?? '', '$');
    }
}
