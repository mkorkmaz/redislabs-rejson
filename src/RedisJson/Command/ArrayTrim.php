<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class ArrayTrim extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.ARRTRIM';

    private function __construct(
        string $key,
        Path $path,
        int $start,
        int $stop
    ) {
        $this->arguments = [$key, $path->getPath(), $start, $stop];
        $this->responseCallback = static function ($result) use ($path) {
            if (!empty($result)) {
                return RedisJson::getArrayResult($result, [$path]);
            }
            return null;
        };
    }

    public static function createCommandWithArguments(
        string $key,
        string $path,
        int $start,
        int $stop
    ): CommandInterface {
        return new self(
            $key,
            new Path($path),
            $start,
            $stop
        );
    }
}
