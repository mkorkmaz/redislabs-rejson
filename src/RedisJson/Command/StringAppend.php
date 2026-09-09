<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class StringAppend extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.STRAPPEND';

    private function __construct(
        string $key,
        Path $path,
        string $json
    ) {
        $this->arguments = [$key, $path->getPath(), $json];
        $this->responseCallback = static fn($result) => RedisJson::getPathResult($result, $path);
    }

    public static function createCommandWithArguments(string $key, string $path, $json): CommandInterface
    {
        return new self(
            $key,
            new Path($path),
            json_encode($json, JSON_THROW_ON_ERROR)
        );
    }
}
