<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use Codeception\Attribute\DataProvider;

final class JsonWriteCommandsTest extends JsonCommandTestCase
{
    #[DataProvider('clients')]
    public function testSetPreservesEmptyArraysObjectsAndScalarValues(string $driver): void
    {
        $module = $this->module($driver);
        foreach ([[], new \stdClass(), 0, false, '', null] as $value) {
            $key = $this->key();
            self::assertSame('OK', $module->set($key, '$', $value));
            self::assertSame(json_encode($value), $module->raw('JSON.GET', $key, '.'));
            self::assertSame(is_object($value) ? [] : $value, $module->get($key, '.'));
            self::assertNull($module->set($key, '$', 'unchanged', 'NX'));
        }
        self::assertNull($module->get($this->key()));
    }

    #[DataProvider('clients')]
    public function testMultipleSetAndMergePatch(string $driver): void
    {
        $module = $this->module($driver);
        $first = $this->key();
        $second = $this->key();
        self::assertSame('OK', $module->mset($first, '$', ['a' => 1], $second, '$', []));
        self::assertSame('[]', $module->raw('JSON.GET', $second, '.'));
        self::assertSame('OK', $module->mset($first, '$.a', 2, $second, '$', ['b' => 3]));
        self::assertSame(2, $module->get($first, '$.a'));
        self::assertSame(3, $module->get($second, '$.b'));
        try {
            self::assertNull($module->mset($first, '$.a', 99, $second, '$[', 1));
        } catch (\RedisException $exception) {
            self::assertStringContainsString('position 2', $exception->getMessage());
        }
        self::assertSame(2, $module->get($first, '$.a'));
        self::assertSame(3, $module->get($second, '$.b'));
        self::assertSame('OK', $module->merge($first, '$', ['a' => null, 'items' => [1, 2], 'nested' => ['x' => 1]]));
        self::assertSame('OK', $module->merge($first, '$', ['items' => [3], 'nested' => ['y' => 2]]));
        self::assertSame(['items' => [3], 'nested' => ['x' => 1, 'y' => 2]], $module->get($first));
        self::assertNull($module->merge($first, '$.absent.child', 1));
        $created = $this->key();
        self::assertSame('OK', $module->merge($created, '$', ['created' => true]));
        self::assertSame(true, $module->get($created, '$.created'));
    }

    #[DataProvider('clients')]
    public function testClearAndToggle(string $driver): void
    {
        $module = $this->module($driver);
        $key = $this->key();
        $module->set($key, '$', ['number' => 1.5, 'items' => [1], 'object' => ['a' => 1], 'text' => 'keep']);
        self::assertSame(3, $module->clear($key, '$.*'));
        self::assertSame(0, $module->clear($key, '$.*'));
        self::assertSame(0, $module->get($key, '$.number'));
        self::assertSame('[]', $module->raw('JSON.GET', $key, '.items'));
        self::assertSame('{}', $module->raw('JSON.GET', $key, '.object'));
        self::assertSame('keep', $module->get($key, '$.text'));
        $module->set($key, '$', ['first' => true, 'second' => false, 'number' => 1]);
        self::assertSame([false, true, null], $module->toggle($key, '$.*'));
        self::assertSame(true, $module->toggle($key, '.first'));
        self::assertSame(false, $module->toggle($key, '$.second'));
        self::assertSame([], $module->toggle($key, '$.absent'));
        $module->set($key, '$', true);
        self::assertSame(false, $module->toggle($key));
    }

    #[DataProvider('clients')]
    public function testFractionalNumberOperations(string $driver): void
    {
        $module = $this->module($driver);
        $key = $this->key();
        $module->set($key, '$', ['number' => 1.5]);
        self::assertSame(1.75, $module->numincrby($key, '$.number', 0.25));
        self::assertSame(0.875, $module->nummultby($key, '.number', 0.5));
    }
}
