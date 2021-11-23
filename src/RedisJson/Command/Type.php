<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class Type extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.TYPE';

    private function __construct(
        string $key,
        Path $path
    ) {
        $this->arguments = [$key, $path->getPath()];
        $this->responseCallback = static function ($result) use ($path) {
            if (!empty($result)) {
                if ($path->isLegacyPath() === false && count($result) === 1) {
                    return $result[0];
                }
                if ($path->isLegacyPath() === false && count($result) > 1) {
                    return $result;
                }
                return $result;
            }
            return null;
        };
    }

    public static function createCommandWithArguments(string $key, $path = '.'): CommandInterface
    {

        return new self(
            $key,
            new Path($path)
        );
    }
}
