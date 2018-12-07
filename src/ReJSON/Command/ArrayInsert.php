<?php
declare(strict_types=1);

namespace Redislabs\Module\ReJSON\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\ReJSON\Path;

final class ArrayInsert extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.ARRINSERT';

    private function __construct(
        string $key,
        Path $path,
        int $index,
        array $jsons
    ) {
        $this->arguments = array_merge([$key, $path->getPath(), $index], $jsons);
    }

    public static function createCommandWithArguments(
        string $key,
        string $path,
        int $index,
        array $jsons
    ) : CommandInterface {
        return new self(
            $key,
            new Path($path),
            $index,
            array_map('json_encode', $jsons)
        );
    }
}
