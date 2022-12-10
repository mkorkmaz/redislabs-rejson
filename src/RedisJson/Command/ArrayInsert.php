<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class ArrayInsert extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.ARRINSERT';

    private function __construct(
        string $key,
        Path $path,
        int $index,
        array $jsons
    ) {
        $this->arguments = array_merge([$key, $path->getPath(), $index], $jsons);
        $this->responseCallback = static fn($result) => RedisJson::getArrayResult($result, [$path]);
    }

    public static function createCommandWithArguments(
        string $key,
        string $path,
        int $index,
        array $jsons
    ): CommandInterface {
        return new self(
            $key,
            new Path($path),
            $index,
            array_map('json_encode', $jsons)
        );
    }
}
