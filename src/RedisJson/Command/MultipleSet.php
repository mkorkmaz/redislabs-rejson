<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Command\CommandAbstract;
use Redislabs\Interfaces\CommandInterface;
use Redislabs\Exceptions\InvalidNumberOfArgumentsException;
use Redislabs\Module\RedisJson\RedisJson;

final class MultipleSet extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.MSET';

    #[\Override]
    protected ?\Closure $responseCallback = RedisJson::getStatusResult(...);

    private function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public static function createCommandWithArguments(array $arguments): CommandInterface
    {
        if ($arguments === [] || count($arguments) % 3 !== 0) {
            throw new InvalidNumberOfArgumentsException('JSON.MSET requires one or more key, path, value triplets.');
        }
        $encoded = [];
        foreach (array_chunk($arguments, 3) as $triplet) {
            array_push($encoded, ...self::encodeTriplet($triplet));
        }
        return new self($encoded);
    }

    private static function encodeTriplet(array $triplet): array
    {
        [$key, $path, $json] = $triplet;
        if (!is_string($key) || !is_string($path)) {
            throw new \InvalidArgumentException('JSON.MSET keys and paths must be strings.');
        }
        return [$key, $path, json_encode($json, JSON_THROW_ON_ERROR)];
    }
}
