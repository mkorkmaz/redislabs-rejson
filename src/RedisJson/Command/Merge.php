<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Command\CommandAbstract;
use Redislabs\Interfaces\CommandInterface;
use Redislabs\Module\RedisJson\RedisJson;

final class Merge extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.MERGE';

    #[\Override]
    protected ?\Closure $responseCallback = RedisJson::getStatusResult(...);

    private function __construct(string $key, string $path, mixed $json)
    {
        $this->arguments = [$key, $path, json_encode($json, JSON_THROW_ON_ERROR)];
    }

    public static function createCommandWithArguments(string $key, string $path, mixed $json): CommandInterface
    {
        return new self($key, $path, $json);
    }
}
