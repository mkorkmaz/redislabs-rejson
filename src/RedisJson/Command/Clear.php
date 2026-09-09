<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Command\CommandAbstract;
use Redislabs\Interfaces\CommandInterface;

final class Clear extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.CLEAR';

    private function __construct(string $key, string $path)
    {
        $this->arguments = [$key, $path];
    }

    public static function createCommandWithArguments(string $key, string $path = '.'): CommandInterface
    {
        return new self($key, $path);
    }
}
