<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use PHPUnit\Framework\TestCase;
use Redislabs\Module\RedisJson\Command\Debug;
use Redislabs\Module\RedisJson\Command\NumberIncrementBy;
use Redislabs\Module\RedisJson\Command\NumberMultiplyBy;
use Redislabs\Module\RedisJson\Path;

final class Php85CommandTest extends TestCase
{
    public function testDebugCopyRetainsArgumentsAndResponseCallback(): void
    {
        $first = Debug::createCommandWithMemorySubCommandAndArguments('first', '$');
        $second = Debug::createCommandWithMemorySubCommandAndArguments('second', '.');
        $help = Debug::createCommandWithHelpSubCommandAndArguments();
        self::assertSame(['MEMORY', 'first', '$'], $first->getArguments());
        self::assertSame(['MEMORY', 'second', '.'], $second->getArguments());
        self::assertSame(['HELP'], $help->getArguments());
        $firstCallback = $first->getResponseCallback();
        $secondCallback = $second->getResponseCallback();
        self::assertSame(16, $firstCallback([16]));
        self::assertSame(32, $secondCallback(32));
        self::assertNull($help->getResponseCallback());
    }

    public function testNumericCallbacksDecodeSingleMultipleAndNullResults(): void
    {
        $commands = [
            NumberIncrementBy::createCommandWithArguments('number', '$', 2),
            NumberMultiplyBy::createCommandWithArguments('number', '$', 2),
        ];
        foreach ($commands as $command) {
            $callback = $command->getResponseCallback();
            self::assertSame(4, $callback('[4]'));
            self::assertSame([4, 8], $callback('[4,8]'));
            self::assertNull($callback('[null]'));
        }
    }

    public function testPathPreservesLegacyAndJsonPathSyntax(): void
    {
        foreach (['.' => true, '.field' => true, '$' => false, '$.field' => false, '' => true] as $value => $legacy) {
            $path = new Path($value);
            self::assertSame($value, $path->getPath());
            self::assertSame($legacy, $path->isLegacyPath());
        }
        $path = new Path();
        self::assertSame('.', $path->getPath());
    }
}
