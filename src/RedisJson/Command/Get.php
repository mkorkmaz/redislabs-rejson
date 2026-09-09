<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Command\CommandAbstract;
use Redislabs\Interfaces\CommandInterface;
use Redislabs\Exceptions\InvalidNumberOfArgumentsException;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class Get extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.GET';

    private const array FORMAT_OPTIONS = ['INDENT', 'NEWLINE', 'SPACE'];

    private function __construct(string $key, array $pathItems, array $options)
    {
        $paths = array_map(static fn(Path $path) => $path->getPath(), $pathItems);
        $this->arguments = [$key, ...$options, ...$paths];
        $this->responseCallback = static function ($result) use ($pathItems) {
            if ($result === null || $result === false) {
                return null;
            }
            return RedisJson::getResult($result, $pathItems);
        };
    }

    public static function createCommandWithArguments(array $arguments): CommandInterface
    {
        if ($arguments === []) {
            throw new InvalidNumberOfArgumentsException('JSON.GET requires a key.');
        }
        $key = array_shift($arguments);
        $options = self::extractOptions($arguments);
        $pathObjects = array_map(static fn($path) => new Path($path), $arguments ?: ['.']);
        return new self($key, $pathObjects, $options);
    }

    private static function extractOptions(array &$arguments): array
    {
        $options = [];
        while ($arguments !== [] && in_array($arguments[0], self::FORMAT_OPTIONS, true)) {
            $option = array_shift($arguments);
            $value = array_shift($arguments);
            if (!is_string($value)) {
                throw new \InvalidArgumentException('JSON.GET ' . $option . ' requires a string value.');
            }
            array_push($options, $option, $value);
        }
        return $options;
    }
}
