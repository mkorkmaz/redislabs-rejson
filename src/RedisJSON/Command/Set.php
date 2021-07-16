<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJSON\Command;

use Redislabs\Interfaces\CommandInterface;
use Redislabs\Command\CommandAbstract;
use Redislabs\Module\RedisJSON\Path;
use Redislabs\Module\RedisJSON\Exceptions\InvalidExistentialModifierException;

use function in_array;

final class Set extends CommandAbstract implements CommandInterface
{
    protected static $command = 'JSON.SET';
    private static $validExistentialModifiers = ['NX', 'XX'];

    private function __construct(
        string $key,
        Path $path,
        string $json
    ) {
        $this->arguments = [$key, $path->getPath(),  $json];
    }

    public function withExistentialModifier(string $existentialModifier): CommandInterface
    {
        if (!in_array($existentialModifier, self::$validExistentialModifiers, true)) {
            throw new InvalidExistentialModifierException(
                sprintf('Invalid existential modifier (%s) used for the command JSON.SET', $existentialModifier)
            );
        }
        $this->arguments[] = $existentialModifier;
        return $this;
    }

    public static function createCommandWithArguments(
        string $key,
        string $path,
        $json,
        ?string $existentialModifier = null
    ): CommandInterface {
        $command = new self(
            $key,
            new Path($path),
            json_encode($json)
        );
        if ($existentialModifier !== null) {
             return $command->withExistentialModifier($existentialModifier);
        }
        return $command;
    }
}
