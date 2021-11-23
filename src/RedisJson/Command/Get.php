<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class Get extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.GET';

    private function __construct(string $key, array $pathItems)
    {
        $paths = array_map(static function (Path $path) {
            return $path->getPath();
        }, $pathItems);
        $this->arguments = [$key];
        $this->arguments = array_merge($this->arguments, ['NOESCAPE'], $paths);
        $this->responseCallback = static function ($result) use ($pathItems) {
            if (!empty($result)) {
                return RedisJson::getResult($result, $pathItems);
            }
            return null;
        };
    }

    public static function createCommandWithArguments(array $arguments): CommandInterface
    {
        $key = array_shift($arguments);
        $pathObjects = [];
        foreach ($arguments as $path) {
            $pathObjects[] = new Path($path);
        }
        if (count($arguments) === 0) {
            $pathObjects[] = new Path('.');
        }
        return new self(
            $key,
            $pathObjects
        );
    }
}
