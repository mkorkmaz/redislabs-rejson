<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use PHPUnit\Framework\TestCase;
use Redislabs\Module\RedisJson\RedisJson;
use RedislabsModulesTest\Support\RedisConnection;

abstract class JsonCommandTestCase extends TestCase
{
    private RedisJson $module;
    private array $keys = [];

    public static function clients(): array
    {
        return ['PhpRedis' => ['phpredis'], 'Predis' => ['predis']];
    }

    protected function module(string $driver): RedisJson
    {
        if ($driver === 'predis') {
            $this->module = RedisJson::createWithPredis(RedisConnection::predis());
            return $this->module;
        }
        $client = RedisConnection::phpRedis();
        $this->module = RedisJson::createWithPhpRedis($client);
        return $this->module;
    }

    protected function key(): string
    {
        $key = '{redislab-json-commands}:' . bin2hex(random_bytes(8));
        $this->keys[] = $key;
        return $key;
    }

    protected function tearDown(): void
    {
        if (!isset($this->module)) {
            return;
        }
        $client = $this->module->getClient();
        foreach ($this->keys as $key) {
            $client->del($key);
        }
    }
}
