<?php

declare(strict_types=1);

namespace Redislabs\Module\ReJSON\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\ReJSON\Path;

final class Resp extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.RESP';

    private function __construct(
        string $key,
        Path $path
    ) {
        $this->arguments = [$key, $path->getPath()];
        // $this->responseCallback = function ($result) {return json_decode($result);};
    }

    public static function createCommandWithArguments(string $key, $path = '.'): CommandInterface
    {
        return new self(
            $key,
            new Path($path)
        );
    }
}
