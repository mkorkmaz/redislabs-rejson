<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;

final class StringLength extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.STRLEN';

    private function __construct(
        string $key,
        Path $path
    ) {
        $this->arguments = [$key, $path->getPath()];
    }

    public static function createCommandWithArguments(string $key, string $path): CommandInterface
    {

        return new self(
            $key,
            new Path($path)
        );
    }
}
