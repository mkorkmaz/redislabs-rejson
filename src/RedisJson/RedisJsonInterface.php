<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson;

use Redislabs\Interfaces\ModuleInterface;
use Predis\Client as PredisClient;
use Redis as PhpRedisClient;
use RedisCluster as PhpRedisClusterClient;

interface RedisJsonInterface extends ModuleInterface
{
    public function set(string $key, string $path, $json, ?string $existentialModifier = null): ?string;
    public function clear(string $key, string $path = '.'): int;
    public function merge(string $key, string $path, mixed $json): ?string;
    public function mset(mixed ...$arguments): ?string;
    public function toggle(string $key, string $path = '.'): bool|array|null;
    public function get(...$arguments);
    public function del(string $key, ?string $path = '.'): int;
    public function forget(string $key, ?string $path = '.'): int;
    public function mget(...$arguments);
    public function type(string $key, ?string $paths = '.');
    public function numincrby(string $key, string $path, int|float $incrementBy);
    /** @deprecated RedisJSON deprecated JSON.NUMMULTBY in version 2.0. */
    public function nummultby(string $key, string $path, int|float $multiplyBy);
    public function strappend(string $key, $json, ?string $path = '.');
    public function strlen(string $key, ?string $path = '.');
    public function arrappend(string $key, string $path, ...$jsons);
    public function arrindex(string $key, string $path, $json, ?int $start = null, ?int $stop = null);
    public function arrinsert(string $key, string $path, int $index, ...$jsons);
    public function arrlen(string $key, string $path = '.');
    public function arrpop(string $key, ?string $path = '.', ?int $index = -1);
    public function arrtrim(string $key, $path, ?int $start = 0, ?int $stop = 0);
    public function objkeys(string $key, ?string $path = '.');
    public function objlen(string $key, ?string $path = '.');
    public function debug(string $subcommand, ?string $key = null, ?string $path = '.');
    public function resp(string $key, ?string $paths = '.');
    public function getClient(): object;
    public function raw(string $command, mixed ...$arguments): mixed;
    public static function createWithPredis(PredisClient $client): static;
    public static function createWithPhpRedis(PhpRedisClient $client): static;
    public static function createWithPhpRedisCluster(PhpRedisClusterClient $client): static;
}
