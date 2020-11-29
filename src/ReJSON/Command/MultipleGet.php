<?php
declare(strict_types=1);

namespace Redislabs\Module\ReJSON\Command;

use Redislabs\Exceptions\InvalidNumberOfArgumentsException;
use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\ReJSON\Path;

final class MultipleGet extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.MGET';

    private function __construct(array $keys, Path $path)
    {
        $this->arguments = $keys;
        $this->arguments[] = $path->getPath();
        $this->responseCallback = static function ($result) {
            return array_map([CommandAbstract::class, 'jsonDecode'], $result);
        };
    }

    public static function createCommandWithArguments(array $arguments) : CommandInterface
    {
        if (count($arguments) <2) {
            throw new InvalidNumberOfArgumentsException(
                sprintf('ReJSON::mget needs at least 2 arguments!, % given', count($arguments))
            );
        }
        $path = array_pop($arguments);
        $keys = $arguments;
        return new self(
            $keys,
            new Path($path)
        );
    }
}
