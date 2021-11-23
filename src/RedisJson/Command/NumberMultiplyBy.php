<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class NumberMultiplyBy extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.NUMMULTBY';

    private function __construct(
        string $key,
        Path $path,
        int $incrementBy
    ) {
        $this->arguments = [$key, $path->getPath(), $incrementBy];
        $this->responseCallback = function ($result) use ($path) {
            return RedisJson::getNumResult($result, [$path]);
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
