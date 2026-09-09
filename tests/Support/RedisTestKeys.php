<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Support;

use Predis\Client;
use Redis;

trait RedisTestKeys
{
    private array $testKeys = [];

    protected function key(string $name): string
    {
        return $this->testKeys[$name] ??= 'redislab-test:' . bin2hex(random_bytes(12)) . ':' . $name;
    }

    protected function deleteTestKeys(Redis|Client $client): void
    {
        foreach ($this->testKeys as $key) {
            $client->del($key);
        }
        $this->testKeys = [];
    }
}
