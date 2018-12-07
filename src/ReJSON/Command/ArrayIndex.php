<?php
declare(strict_types=1);

namespace Redislabs\Module\ReJSON\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\ReJSON\Path;

final class ArrayIndex extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.ARRINDEX';

    private function __construct(
        string $key,
        Path $path,
        string $json,
        int $start,
        int $stop
    ) {
        $this->arguments = [$key, $path->getPath(), $json, $start, $stop];
    }

    public static function createCommandWithArguments(
        string $key,
        string $path,
        $json,
        int $start,
        int $stop
    ) : CommandInterface {
        return new self(
            $key,
            new Path($path),
            json_encode($json),
            $start,
            $stop
        );
    }
}
