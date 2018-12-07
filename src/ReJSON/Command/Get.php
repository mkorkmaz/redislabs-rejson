<?php
declare(strict_types=1);

namespace Redislabs\Module\ReJSON\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\ReJSON\Path;

final class Get extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.GET';

    private function __construct(
        string $key,
        array $paths
    ) {
        $paths = array_map(function (Path $path) {
            return $path->getPath();
        }, $paths);
        $this->arguments = [$key];
        $this->arguments = array_merge($this->arguments, $paths);
        $this->responseCallback = function ($result) {
            return json_decode($result);
        };
    }

    public static function createCommandWithArguments(string $key, $paths = '.') : CommandInterface
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
