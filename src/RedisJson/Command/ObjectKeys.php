<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class ObjectKeys extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.OBJKEYS';

    private function __construct(string $key, Path $path)
    {
        $this->arguments = [$key,  $path->getPath()];
        $this->responseCallback = static fn($result) => RedisJson::getPathResult($result, $path);
    }

    public static function createCommandWithArguments(string $key, string $path): CommandInterface
    {
        return new self(
            $key,
            new Path($path)
        );
    }
}
