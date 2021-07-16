<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJSON\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJSON\Path;

final class ObjectLength extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.OBJLEN';

    private function __construct(string $key, Path $path)
    {
        $this->arguments = [$key,  $path->getPath()];
    }

    public static function createCommandWithArguments(string $key, string $path): CommandInterface
    {
        return new self(
            $key,
            new Path($path)
        );
    }
}
