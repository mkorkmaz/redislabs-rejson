<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use Codeception\Attribute\DataProvider;
use PHPUnit\Framework\TestCase;
use Redislabs\Exceptions\InvalidNumberOfArgumentsException;
use Redislabs\Interfaces\RedisClientInterface;
use Redislabs\Module\RedisJson\Command\ArrayIndex;
use Redislabs\Module\RedisJson\Command\Get;
use Redislabs\Module\RedisJson\RedisJson;

final class JsonCommandArgumentsTest extends TestCase
{
    public function testDebugMemoryRoutesTheKeyAndNormalizesTheResponse(): void
    {
        $client = $this->createMock(RedisClientInterface::class);
        $client->expects($this->once())->method('rawCommand')
            ->with('JSON.DEBUG', ['MEMORY', 'document', '$'])->willReturn([64]);
        $module = new RedisJson($client);
        self::assertSame(64, $module->debug('MEMORY', 'document', '$'));
    }

    public function testDebugHelpDoesNotSendKeyOrPathArguments(): void
    {
        $client = $this->createMock(RedisClientInterface::class);
        $client->expects($this->once())->method('rawCommand')
            ->with('JSON.DEBUG', ['HELP'])->willReturn(['MEMORY <key> [path]']);
        $module = new RedisJson($client);
        self::assertSame(['MEMORY <key> [path]'], $module->debug('HELP'));
    }

    public function testGetUsesCurrentFormatOptionsWithoutNoescape(): void
    {
        $command = Get::createCommandWithArguments(['doc', 'INDENT', '  ', 'NEWLINE', "\n", 'SPACE', ' ', '$']);
        self::assertSame(['doc', 'INDENT', '  ', 'NEWLINE', "\n", 'SPACE', ' ', '$'], $command->getArguments());
        $default = Get::createCommandWithArguments(['doc']);
        self::assertSame(['doc', '.'], $default->getArguments());
    }

    public function testArrayIndexOmitsOptionalRangeArguments(): void
    {
        foreach ([[null, null, []], [2, null, [2]], [null, 3, [0, 3]], [-2, -1, [-2, -1]]] as [$start, $stop, $range]) {
            $command = ArrayIndex::createCommandWithArguments('doc', '$', 'value', $start, $stop);
            self::assertSame(['doc', '$', '"value"', ...$range], $command->getArguments());
        }
    }

    public static function invalidArguments(): array
    {
        return [
            'get without key' => ['get', [], InvalidNumberOfArgumentsException::class],
            'get incomplete format' => ['get', ['doc', 'INDENT'], \InvalidArgumentException::class],
            'mget without path' => ['mget', ['doc'], InvalidNumberOfArgumentsException::class],
            'mset empty' => ['mset', [], InvalidNumberOfArgumentsException::class],
            'mset incomplete triplet' => ['mset', ['doc', '$'], InvalidNumberOfArgumentsException::class],
            'mset invalid key' => ['mset', [1, '$', 1], \InvalidArgumentException::class],
            'mset invalid path' => ['mset', ['doc', [], 1], \InvalidArgumentException::class],
            'arrappend without value' => ['arrappend', ['doc', '$'], InvalidNumberOfArgumentsException::class],
            'arrinsert without value' => ['arrinsert', ['doc', '$', 0], InvalidNumberOfArgumentsException::class],
            'debug memory without key' => ['debug', ['MEMORY'], InvalidNumberOfArgumentsException::class],
        ];
    }

    #[DataProvider('invalidArguments')]
    public function testInvalidArgumentsDoNotReachRedis(string $method, array $arguments, string $exception): void
    {
        $client = $this->createMock(RedisClientInterface::class);
        $client->expects($this->never())->method('rawCommand');
        $module = new RedisJson($client);
        $this->expectException($exception);
        $module->{$method}(...$arguments);
    }

    public static function invalidJson(): array
    {
        return [
            'set' => ['set', ['doc', '$', "\xB1"]],
            'merge' => ['merge', ['doc', '$', "\xB1"]],
            'mset validates all values' => ['mset', ['first', '$', 1, 'second', '$', "\xB1"]],
            'arrappend' => ['arrappend', ['doc', '$', "\xB1"]],
            'arrinsert' => ['arrinsert', ['doc', '$', 0, "\xB1"]],
            'nonfinite increment' => ['numincrby', ['doc', '$', INF]],
            'nonfinite multiplier' => ['nummultby', ['doc', '$', NAN]],
        ];
    }

    #[DataProvider('invalidJson')]
    public function testInvalidJsonDoesNotReachRedis(string $method, array $arguments): void
    {
        $client = $this->createMock(RedisClientInterface::class);
        $client->expects($this->never())->method('rawCommand');
        $module = new RedisJson($client);
        $this->expectException(\JsonException::class);
        $module->{$method}(...$arguments);
    }
}
