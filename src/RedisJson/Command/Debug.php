<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Exceptions\InvalidDebugSubcommandException;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

use function in_array;

final class Debug extends CommandAbstract implements CommandInterface
{
    #[\Override]
    protected static string $command = 'JSON.DEBUG';

    private const array VALID_SUBCOMMANDS = ['MEMORY', 'HELP'];

    private function __construct(string $subcommand)
    {
        $this->arguments = [$subcommand];
    }

    private function withArguments(string $key, Path $path): self
    {
        return clone($this, ['arguments' => [...$this->arguments, $key, $path->getPath()]]);
    }

    public static function createCommandWithArguments(string $subcommand, ?string $key, string $path): CommandInterface
    {
        if (!in_array($subcommand, self::VALID_SUBCOMMANDS, true)) {
            throw new InvalidDebugSubcommandException(
                sprintf('%s is not a valid debug subcommand.', $subcommand)
            );
        }
        if ($subcommand === 'MEMORY' && $key === null) {
            throw new \Redislabs\Exceptions\InvalidNumberOfArgumentsException('JSON.DEBUG MEMORY requires a key.');
        }
        if ($subcommand === 'MEMORY') {
            return self::createCommandWithMemorySubCommandAndArguments($key, $path);
        }
        return self::createCommandWithHelpSubCommandAndArguments();
    }

    public static function createCommandWithMemorySubCommandAndArguments(string $key, string $path): CommandInterface
    {
        $debugObj = new self(
            'MEMORY'
        );
        $pathObject = new Path($path);
        $debugObj->responseCallback = static fn($result) => RedisJson::getPathResult($result, $pathObject);
        return $debugObj->withArguments($key, $pathObject);
    }

    public static function createCommandWithHelpSubCommandAndArguments(): CommandInterface
    {
        return new self(
            'HELP'
        );
    }
}
