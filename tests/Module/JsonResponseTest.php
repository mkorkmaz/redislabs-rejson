<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use Codeception\Attribute\DataProvider;

final class JsonResponseTest extends JsonCommandTestCase
{
    #[DataProvider('clients')]
    public function testArrayCommandsKeepEveryMatchAndZeroValue(string $driver): void
    {
        $module = $this->module($driver);
        $key = $this->key();
        $module->set($key, '$', ['a' => [0], 'b' => [false], 'c' => [], 'd' => 'wrong']);
        self::assertSame([1, 1, 0, null], $module->arrlen($key, '$.*'));
        self::assertSame([0, false, null, null], $module->arrpop($key, '$.*'));
        self::assertSame([], $module->arrpop($key, '$.absent'));
        self::assertSame([1, 1, 1, null], $module->arrappend($key, '$.*', 3));
        self::assertSame([0, 0, 0, null], $module->arrindex($key, '$.*', 3));
        self::assertSame([2, 2, 2, null], $module->arrinsert($key, '$.*', 0, 2));
        self::assertSame([0, 0, 0, null], $module->arrtrim($key, '$.*', 10, 20));
        self::assertSame(0, $module->arrlen($key, '.a'));
        self::assertSame(0, $module->arrtrim($key, '.a', 0, 1));
        $module->set($key, '$', [0]);
        self::assertSame(0, $module->arrindex($key, '.', 0));
        self::assertSame(0, $module->arrpop($key, '.'));
    }

    #[DataProvider('clients')]
    public function testEmptyAndObjectResultsRetainTheirShape(string $driver): void
    {
        $module = $this->module($driver);
        $key = $this->key();
        $module->set($key, '$', ['text' => '', 'object' => new \stdClass(), 'values' => [1]]);
        self::assertSame(0, $module->strlen($key, '.text'));
        self::assertSame(0, $module->strappend($key, '', '.text'));
        self::assertSame(0, $module->objlen($key, '.object'));
        self::assertSame([], $module->objkeys($key, '.object'));
        self::assertSame([], $module->objkeys($key, '$.object'));
        self::assertSame(['text', 'object', 'values'], $module->objkeys($key, '$'));
        self::assertSame([], $module->objkeys($key, '$.absent'));
        self::assertSame([], $module->type($key, '$.absent'));
    }

    #[DataProvider('clients')]
    public function testGetFormattingAndMultipleLegacyPaths(string $driver): void
    {
        $module = $this->module($driver);
        $key = $this->key();
        $module->set($key, '$', ['object' => ['field' => 1], 'list' => [2], 'zero' => 0]);
        $legacy = $module->get($key, '.object', '.list');
        self::assertSame(['field' => 1], $legacy['.object']);
        self::assertSame([2], $legacy['.list']);
        $mixed = $module->get($key, '.object', '$.list');
        self::assertSame(['field' => 1], $mixed['.object']);
        self::assertSame([2], $mixed['$.list']);
        self::assertSame(0, $module->get($key, 'INDENT', '  ', 'NEWLINE', "\n", 'SPACE', ' ', '$.zero'));
        self::assertSame([$key => [0]], $module->mget($key, '$.zero'));
        $missing = $this->key();
        self::assertSame([$missing => null], $module->mget($missing, '$'));
    }
}
