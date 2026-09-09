<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class ArrayAppend extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.ARRAPPEND';

    private function __construct(
        string $key,
        Path $path,
        array $jsons
    ) {
        $this->arguments = array_merge([$key, $path->getPath()], $jsons);
        $this->responseCallback = static fn($result) => RedisJson::getArrayResult($result, [$path]);
    }

    public static function createCommandWithArguments(string $key, string $path, array $jsons): CommandInterface
    {
        if ($jsons === []) {
            throw new \Redislabs\Exceptions\InvalidNumberOfArgumentsException(
                'ArrayAppend requires at least one JSON value.'
            );
        }
        return new self(
            $key,
            new Path($path),
            array_map(static fn($json) => json_encode($json, JSON_THROW_ON_ERROR), $jsons)
        );
    }
}
