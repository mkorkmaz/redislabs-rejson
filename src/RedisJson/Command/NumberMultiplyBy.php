<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class NumberMultiplyBy extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.NUMMULTBY';

    #[\Override]
    protected ?\Closure $responseCallback = RedisJson::getNumResult(...);

    private function __construct(
        string $key,
        Path $path,
        int|float $incrementBy
    ) {
        $this->arguments = [$key, $path->getPath(), json_encode($incrementBy, JSON_THROW_ON_ERROR)];
    }

    public static function createCommandWithArguments(
        string $key,
        string $path,
        int|float $incrementBy
    ): CommandInterface {

        return new self(
            $key,
            new Path($path),
            $incrementBy
        );
    }
}
