<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Exceptions\InvalidNumberOfArgumentsException;
use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;

final class MultipleGet extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.MGET';

    private function __construct(array $keys, Path $path)
    {
        $this->arguments = $keys;
        $this->arguments[] = $path->getPath();
        $this->responseCallback = static function ($result) use ($keys) {
            $resultArray = array_map([CommandAbstract::class, 'jsonDecode'], $result);
            $resultToReturn = [];
            $keysCount = count($keys);
            for ($i = 0; $i < $keysCount; $i++) {
                $resultToReturn[$keys[$i]] = $resultArray[$i];
            }
            return $resultToReturn;
        };
    }

    public static function createCommandWithArguments(array $arguments): CommandInterface
    {
        if (count($arguments) < 2) {
            throw new InvalidNumberOfArgumentsException(
                sprintf('ReJson::mget needs at least 2 arguments!, % given', count($arguments))
            );
        }
        $path = array_pop($arguments);
        $keys = $arguments;
        return new self(
            $keys,
            new Path($path)
        );
    }
}
