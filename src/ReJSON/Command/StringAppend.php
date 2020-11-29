<?php

declare(strict_types=1);

namespace Redislabs\Module\ReJSON\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\ReJSON\Path;

final class StringAppend extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.STRAPPEND';

    private function __construct(
        string $key,
        Path $path,
        string $json
    ) {
        $this->arguments = [$key, $path->getPath(), $json];
    }

    public static function createCommandWithArguments(string $key, string $path, $json): CommandInterface
    {
        return new self(
            $key,
            new Path($path),
            json_encode($json)
        );
    }
}
