<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;

final class NumberIncrementBy extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.NUMINCRBY';

    private function __construct(
        string $key,
        Path $path,
        int $incrementBy
    ) {
        $this->arguments = [$key, $path->getPath(), $incrementBy];
        $this->responseCallback = function ($result) {
            return json_decode($result);
        };
    }

    public static function createCommandWithArguments(string $key, string $path, int $incrementBy): CommandInterface
    {

        return new self(
            $key,
            new Path($path),
            $incrementBy
        );
    }
}
