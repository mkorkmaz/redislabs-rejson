<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJSON;

use Redislabs\Interfaces\ModuleInterface;
use Predis\ClientInterface as PredisClient;
use Redis as PhpRedisClient;

interface RedisJsonInterface extends ModuleInterface
{
    public function set(string $key, string $path, $json, ?string $existentialModifier = null);
    public function get(string $key, $paths = null);
    public function getArray(string $key, $paths = null);
    public function del(string $key, ?string $path = '.'): int;
    public function forget(string $key, ?string $path = '.'): int;
    public function mget(...$arguments);
    public function mgetArray(...$arguments);
    public function type(string $key, ?string $paths = '.');
    public function numincrby(string $key, string $path, int $incrementBy);
    public function nummultby(string $key, string $path, int $multiplyBy);
    public function strappend(string $key, $json, ?string $path = '.');
    public function strlen(string $key, ?string $path = '.');
    public function arrappend(string $key, string $path, ...$jsons);
    public function arrindex(string $key, string $path, $json, ?int $start = 0, ?int $stop = 0);
    public function arrinsert(string $key, string $path, int $index, ...$jsons);
    public function arrlen(string $key, string $path = '.');
    public function arrpop(string $key, ?string $path = '.', ?int $index = -1);
    public function arrtrim(string $key, $path, ?int $start = 0, ?int $stop = 0);
    public function objkeys(string $key, ?string $path = '.');
    public function objlen(string $key, ?string $path = '.');
    public function debug(string $subcommand, ?string $key = null, ?string $path = '.');
    public function resp(string $key, ?string $paths = '.');
    public function getClient();
    public function raw(string $command, ...$arguments);
    public static function createWithPredis(PredisClient $client);
    public static function createWithPhpRedis(PhpRedisClient $client);
}
