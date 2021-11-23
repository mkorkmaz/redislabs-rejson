<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJson\Exceptions\InvalidDebugSubcommandException;
use Redislabs\Module\RedisJson\Path;

use function in_array;

final class Debug extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.DEBUG';

    private static $validSubCommands = ['MEMORY', 'HELP'];

    private function __construct(string $subcommand)
    {
        $this->arguments = [$subcommand];
    }

    private function addArgument($arg)
    {
        $this->arguments[] = $arg;
    }

    private function withArguments(string $key, Path $path)
    {
        $new = clone $this;
        $new->addArgument($key);
        $new->addArgument($path->getPath());
        return $new;
    }

    public static function createCommandWithArguments(string $subcommand, ?string $key, string $path): CommandInterface
    {
        if (!in_array($subcommand, self::$validSubCommands, true)) {
            throw new InvalidDebugSubcommandException(
                sprintf('%s is not a valid debug subcommand.', $subcommand)
            );
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
        $debugObj->responseCallback = static function ($result) use ($pathObject) {
            if (!empty($result)) {
                if ($pathObject->isLegacyPath() === false && count($result) === 1) {
                    return $result[0];
                }
                if ($pathObject->isLegacyPath() === false && count($result) > 1) {
                    return $result;
                }
                return $result;
            }
            return null;
        };
        return $debugObj->withArguments($key, $pathObject);
    }

    public static function createCommandWithHelpSubCommandAndArguments(): CommandInterface
    {
        return new self(
            'HELP'
        );
    }
}
