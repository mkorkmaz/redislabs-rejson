<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;

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
