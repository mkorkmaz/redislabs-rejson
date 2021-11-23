<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class ArrayPop extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.ARRPOP';

    private function __construct(
        string $key,
        Path $path,
        int $index
    ) {
        $this->arguments = [$key, $path->getPath(), $index];
        $this->responseCallback = static function ($result) use ($path) {
            var_dump($result);
            return RedisJson::getArrayResult($result, [$path]);
        };
    }

    public static function createCommandWithArguments(string $key, string $path, int $index): CommandInterface
    {
        return new self(
            $key,
            new Path($path),
            $index
        );
    }
}
