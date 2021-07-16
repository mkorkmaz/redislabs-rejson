<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJSON\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJSON\Path;

final class Get extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.GET';

    private function __construct(
        string $key,
        array $paths
    ) {
        $paths = array_map(static function (Path $path) {
            return $path->getPath();
        }, $paths);
        $this->arguments = [$key];
        $this->arguments = array_merge($this->arguments, ['NOESCAPE'], $paths);
        $this->responseCallback = function ($result) {
            if (!empty($result)) {
                return json_decode($result);
            }
            return null;
        };
    }

    public static function createCommandWithArguments(string $key, $paths = '.'): CommandInterface
    {
        $pathObjects = [];
        if (!is_array($paths)) {
            $paths = (array) $paths;
        }
        foreach ($paths as $path) {
            $pathObjects[] = new Path($path);
        }

        return new self(
            $key,
            $pathObjects
        );
    }
}
