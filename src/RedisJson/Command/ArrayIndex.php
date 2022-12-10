<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class ArrayIndex extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.ARRINDEX';

    private function __construct(
        string $key,
        Path $path,
        string $json,
        int $start,
        int $stop
    ) {
        $this->arguments = [$key, $path->getPath(), $json, $start, $stop];
        $this->responseCallback = static fn($result) => RedisJson::getArrayResult($result, [$path]);
    }

    public static function createCommandWithArguments(
        string $key,
        string $path,
        $json,
        int $start,
        int $stop
    ): CommandInterface {
        return new self(
            $key,
            new Path($path),
            json_encode($json, JSON_THROW_ON_ERROR),
            $start,
            $stop
        );
    }
}
