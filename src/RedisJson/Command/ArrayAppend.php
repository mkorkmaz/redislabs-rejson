<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;

final class ArrayAppend extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.ARRAPPEND';

    private function __construct(
        string $key,
        Path $path,
        array $jsons
    ) {
        $this->arguments = array_merge([$key, $path->getPath()], $jsons);
    }

    public static function createCommandWithArguments(string $key, string $path, array $jsons): CommandInterface
    {
        return new self(
            $key,
            new Path($path),
            array_map('json_encode', $jsons)
        );
    }
}
