<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use PHPUnit\Framework\TestCase;
use RedisCluster;
use Redislabs\Module\RedisJson\RedisJson;
use Redislabs\Module\ReJSON\ReJSON;

final class RedisClusterTest extends TestCase
{
    private RedisCluster $client;
    private array $keys = [];

    protected function setUp(): void
    {
        $seeds = getenv('REDIS_CLUSTER_SEEDS');
        if ($seeds === false || $seeds === '') {
            self::markTestSkipped('Set REDIS_CLUSTER_SEEDS to run RedisJSON cluster integration tests.');
        }
        $password = getenv('REDIS_CLUSTER_PASSWORD');
        $this->client = new RedisCluster(
            null,
            explode(',', $seeds),
            2.0,
            2.0,
            false,
            $password === false || $password === '' ? null : $password
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            $this->client->del($key);
        }
    }

    private function key(string $tag): string
    {
        $key = 'redislab-cluster-test:{' . $tag . '}:' . bin2hex(random_bytes(8));
        $this->keys[] = $key;
        return $key;
    }

    public function testJsonCommandsAcrossAllMasters(): void
    {
        $module = RedisJson::createWithPhpRedisCluster($this->client);
        self::assertSame($this->client, $module->getClient());
        $masters = $this->client->_masters();
        self::assertGreaterThanOrEqual(3, count($masters));
        $owners = [];
        // These tags cover all three slot ranges in a standard three-master cluster.
        foreach (['a', 'b', 'c'] as $tag) {
            $key = $this->key($tag);
            $owners[] = $this->client->rawCommand($key, 'CLUSTER', 'MYID');
            self::assertSame('OK', $module->set($key, '$', ['number' => 2, 'text' => 'a', 'items' => [1, 2]]));
            self::assertSame(2, $module->get($key, '$.number'));
            self::assertSame(2, $module->get($key, '.number'));
            self::assertSame('integer', $module->type($key, '$.number'));
            self::assertSame(5, $module->numincrby($key, '$.number', 3));
            self::assertSame(10, $module->nummultby($key, '$.number', 2));
            self::assertSame(2, $module->strappend($key, 'b', '$.text'));
            self::assertSame(2, $module->strlen($key, '$.text'));
            self::assertSame(3, $module->arrappend($key, '$.items', 3));
            self::assertSame(1, $module->arrindex($key, '$.items', 2));
            self::assertSame(4, $module->arrinsert($key, '$.items', 0, 0));
            self::assertSame(4, $module->arrlen($key, '$.items'));
            self::assertSame(3, $module->arrpop($key, '$.items'));
            self::assertSame(2, $module->arrtrim($key, '$.items', 0, 1));
            self::assertSame(3, $module->objlen($key, '$'));
            self::assertSame(['number', 'text', 'items'], $module->objkeys($key, '.'));
            self::assertGreaterThan(0, $module->debug('MEMORY', $key, '$'));
            self::assertIsArray($module->resp($key));
            self::assertSame('[10]', $module->raw('JSON.GET', $key, '$.number'));
            self::assertTrue($module->expire($key, 60));
            self::assertSame(1, $module->forget($key, '$.text'));
            self::assertSame(1, $module->del($key));
        }
        self::assertCount(3, array_unique($owners));
        self::assertNotEmpty($module->debug('HELP'));
    }

    public function testLegacyAliasAndSameSlotMultipleGet(): void
    {
        $module = ReJSON::createWithPhpRedisCluster($this->client);
        $first = $this->key('group');
        $second = $this->key('group');
        $missing = $this->key('group');
        self::assertSame('OK', $module->set($first, '$', ['value' => 1]));
        self::assertSame('OK', $module->set($second, '$', ['value' => 2]));
        self::assertSame(
            [$first => [1], $second => [2], $missing => null],
            $module->mget($first, $second, $missing, '$.value')
        );
        self::assertSame([$second => 2, $first => 1], $module->mget($second, $first, '.value'));
    }

    public function testNewWriteCommandsAcrossMasters(): void
    {
        $module = RedisJson::createWithPhpRedisCluster($this->client);
        foreach (['a', 'b', 'c'] as $tag) {
            $first = $this->key($tag);
            $second = $this->key($tag);
            self::assertSame('OK', $module->mset($first, '$', ['flag' => true], $second, '$', [1, 2]));
            self::assertSame(false, $module->toggle($first, '$.flag'));
            self::assertSame('OK', $module->merge($first, '$', ['flag' => null, 'number' => 1.5]));
            self::assertSame(['number' => 1.5], $module->get($first));
            self::assertSame(1.75, $module->numincrby($first, '$.number', 0.25));
            self::assertSame(1, $module->clear($second));
            self::assertSame(0, $module->arrlen($second));
        }
    }

    public function testMultipleSetRejectsDifferentSlotsWithoutPartialWrites(): void
    {
        $module = RedisJson::createWithPhpRedisCluster($this->client);
        $first = $this->key('a');
        $second = $this->key('b');
        $this->client->clearLastError();
        try {
            self::assertNull($module->mset($first, '$', 1, $second, '$', 2));
            self::assertStringContainsString('CROSSSLOT', $this->client->getLastError());
        } catch (\RedisClusterException $exception) {
            self::assertStringContainsString('CROSSSLOT', $exception->getMessage());
        }
        self::assertSame(0, $this->client->exists($first));
        self::assertSame(0, $this->client->exists($second));
    }
}
