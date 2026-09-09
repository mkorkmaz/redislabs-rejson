<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Command\CommandAbstract;
use Redislabs\Interfaces\CommandInterface;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class Toggle extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.TOGGLE';

    private function __construct(string $key, Path $path)
    {
        $this->arguments = [$key, $path->getPath()];
        $this->responseCallback = static function ($result) use ($path) {
            $result = RedisJson::getPathResult($result, $path);
            return is_array($result) ? array_map(self::booleanResult(...), $result) : self::booleanResult($result);
        };
    }

    private static function booleanResult(mixed $result): ?bool
    {
        return match ($result) {
            true, 1, 'true' => true,
            0, 'false' => false,
            null, false => null,
            default => throw new \UnexpectedValueException('Unexpected JSON.TOGGLE response.'),
        };
    }

    public static function createCommandWithArguments(string $key, string $path = '.'): CommandInterface
    {
        return new self($key, new Path($path));
    }
}
