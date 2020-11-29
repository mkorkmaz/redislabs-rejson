<?php

declare(strict_types=1);

namespace Redislabs\Module\ReJSON\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\ReJSON\Path;

final class Type extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.TYPE';

    private function __construct(
        string $key,
        Path $path
    ) {
        $this->arguments = [$key, $path->getPath()];
    }

    public static function createCommandWithArguments(string $key, $path = '.'): CommandInterface
    {

        return new self(
            $key,
            new Path($path)
        );
    }
}
