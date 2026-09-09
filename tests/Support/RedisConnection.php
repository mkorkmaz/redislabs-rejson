<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Support;

use Predis\Client;
use Redis;

final class RedisConnection
{
    public static function phpRedis(): Redis
    {
        $settings = self::settings();
        $client = new Redis();
        $client->connect($settings['host'], $settings['port'], 2.0);
        if ($settings['password'] !== null && $settings['password'] !== '') {
            $client->auth($settings['password']);
        }
        return $client;
    }

    public static function predis(): Client
    {
        return new Client([...self::settings(), 'timeout' => 2.0, 'read_write_timeout' => 2.0]);
    }

    private static function settings(): array
    {
        $password = getenv('REDIS_PASSWORD');
        return [
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('REDIS_PORT') ?: 6379),
            'password' => $password === false ? null : $password,
        ];
    }
}
