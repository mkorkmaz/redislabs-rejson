<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class ArrayInsert extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.ARRINSERT';

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
        if ($jsons === []) {
            throw new \Redislabs\Exceptions\InvalidNumberOfArgumentsException(
                'ArrayInsert requires at least one JSON value.'
            );
        }
        return new self(
            $key,
            new Path($path),
            $index,
            array_map(static fn($json) => json_encode($json, JSON_THROW_ON_ERROR), $jsons)
        );
    }
}
