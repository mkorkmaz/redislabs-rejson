# RedisJSON-PHP: Redislabs RedisJSON Version 2 Client for PHP

RedisJSON-PHP provides a client for Redislabs' ReJSON Module for PHP. This library supports both widely used redis clients ([PECL Redis Extension](https://github.com/phpredis/phpredis/#readme) and [Predis](https://github.com/nrk/predis)).


[![CI](https://github.com/mkorkmaz/redislabs-rejson/actions/workflows/ci.yml/badge.svg)](https://github.com/mkorkmaz/redislabs-rejson/actions/workflows/ci.yml) [![Coverage Status](https://coveralls.io/repos/github/mkorkmaz/redislabs-rejson/badge.svg?branch=master)](https://coveralls.io/github/mkorkmaz/redislabs-rejson?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mkorkmaz/redislabs-rejson/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mkorkmaz/redislabs-rejson/?branch=master) [![Latest Stable Version](https://poser.pugx.org/mkorkmaz/redislabs-rejson/v/stable)](https://packagist.org/packages/mkorkmaz/redislabs-rejson) [![Total Downloads](https://poser.pugx.org/mkorkmaz/redislabs-rejson/downloads)](https://packagist.org/packages/mkorkmaz/redislabs-rejson) [![Latest Unstable Version](https://poser.pugx.org/mkorkmaz/redislabs-rejson/v/unstable)](https://packagist.org/packages/mkorkmaz/redislabs-rejson) [![License](https://poser.pugx.org/mkorkmaz/redislabs-rejson/license)](https://packagist.org/packages/mkorkmaz/redislabs-rejson)


## About RedisJSON

"RedisJSON is a Redis module that implements ECMA-404 The JSON Data Interchange Standard as a native data type. It allows storing, updating and fetching JSON values from Redis keys (documents)".

[More info about RedisJSON](https://oss.redislabs.com/redisjson/).


## RedisJSON-PHP Interface

Commands are named after lowercase version of the original RedisJSON commands.

```php
<?php

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
```

## Installation

The recommended method to install RedisJSON-PHP for ReJSON is with composer.

```bash
composer require mkorkmaz/redislabs-rejson
```

If you use Redis ReJSON module version 1.0:

```bash
composer require mkorkmaz/redislabs-rejson:"^1.0"
```

## Usage

You need PECL Redis Extension or Predis to use ReJSON-PHP.

### Creating RedisJSON Client

##### Example for PECL Redis Extension

```php
<?php
declare(strict_types=1);

use Redis;
use Redislabs\Module\RedisJson\RedisJson;

$redisClient = new Redis();
$redisClient->connect('127.0.0.1');
$reJSON = RedisJson::createWithPhpRedis($redisClient);
```

##### Example for Predis

```php
<?php
declare(strict_types=1);

use Predis;
use Redislabs\Module\RedisJson\RedisJson;

$redisClient = new Predis\Client();
$redisJson = RedisJson::createWithPredis($redisClient);
```

### Running commands
- **$key (or $keys - array that contains $key items)** parameters are all string.
- **$json (or $jsons - array that contains $json items)** parameters can be any type of json encodable data (array, int, string, stdClass, any JsonSerializable object etc...).
- Commands automatically performs json encoding these data. Functions also returns json decoded data if the response is json string.


```php
<?php

$redisJson->set('test', '.', ['foo'=>'bar'], 'NX');
$redisJson->set('test', '.baz', 'qux');
$redisJson->set('test', '.baz', 'quux', 'XX');
$redisJson->set('test2', '.', ['foo2'=>'bar2']);
$baz = $redisJson->get('test', '.baz');

var_dump($baz);
// Prints string(4) "quux"
$array = $redisJson->get('test', '.');
var_dump($array);
/*
Prints result as an array instead of an object
array(2) {
  ["foo"]=>
  string(3) "bar"
  ["baz"]=>
  string(4) "quux"
}

*/
$array = $redisJson->mget('test', 'test2', '.');
var_dump($array);
/*
Prints result as an associative array instead of an object
array(2) {
  ["test"]=>
  array(2) {
    ["foo"]=>
    string(3) "bar"
    ["baz"]=>
    string(4) "quux"
  }
  ["test2"]=>
  array(1) {
    ["foo2"]=>
    string(3) "bar2"
  }
}
*/

```


## Current RedisJSON command support

The public API covers the [Redis JSON command list](https://redis.io/docs/latest/operate/oss_and_stack/stack-with-enterprise/json/commands/)
and [JSON.MSET](https://redis.io/docs/latest/commands/json.mset/).
Full command coverage requires RedisJSON 2.6 or later; `merge()` and `mset()` were
introduced in 2.6. The integration suite uses Redis Stack `7.4.0-v8`.

| Redis commands | PHP methods |
| --- | --- |
| JSON.SET, JSON.GET | `set()`, `get()` |
| JSON.MSET, JSON.MGET, JSON.MERGE | `mset()`, `mget()`, `merge()` |
| JSON.DEL, JSON.FORGET, JSON.CLEAR | `del()`, `forget()`, `clear()` |
| JSON.TOGGLE, JSON.TYPE | `toggle()`, `type()` |
| JSON.NUMINCRBY, JSON.NUMMULTBY | `numincrby()`, `nummultby()` |
| JSON.ARRAPPEND, JSON.ARRINSERT, JSON.ARRINDEX | `arrappend()`, `arrinsert()`, `arrindex()` |
| JSON.ARRLEN, JSON.ARRPOP, JSON.ARRTRIM | `arrlen()`, `arrpop()`, `arrtrim()` |
| JSON.OBJKEYS, JSON.OBJLEN | `objkeys()`, `objlen()` |
| JSON.STRAPPEND, JSON.STRLEN | `strappend()`, `strlen()` |
| JSON.DEBUG HELP, JSON.DEBUG MEMORY, JSON.RESP | `debug()`, `resp()` |

`forget()` remains an alias for `del()`. RedisJSON deprecated `JSON.NUMMULTBY`
in version 2.0; `nummultby()` remains available for compatibility.

### New write commands

Values are PHP values and are JSON-encoded by the library. `mset()` accepts a
flat sequence of key, path, value triplets and sends one atomic Redis command.
Use a shared hash tag for every key when running on RedisCluster.

```php
$json = RedisJson::createWithPhpRedis($redisClient);
$json->mset(
    '{account:42}:profile', '$', ['enabled' => true, 'count' => 1.5],
    '{account:42}:items', '$', []
);
$json->merge('{account:42}:profile', '$', ['count' => null, 'name' => 'Ada']);
$enabled = $json->toggle('{account:42}:profile', '$.enabled'); // false
$cleared = $json->clear('{account:42}:items'); // 0: already empty
```

`merge()` follows JSON Merge Patch: object members with null values are removed,
objects are merged recursively, and arrays are replaced. `clear()` returns the
number of values cleared. `toggle()` returns a PHP boolean, null for a non-boolean
match, or an array for multiple matches. Missing JSONPath matches return `[]`.

`set()`, `merge()`, and `mset()` return `'OK'` on success or null when the write is
not applied. Server errors retain the underlying client's behavior: PhpRedis can
throw an exception or return false with `getLastError()`, while the current Predis
raw adapter returns error strings, which these status methods map to null.
Validate application results rather than assuming that every write succeeded.

### Updated arguments and responses

- `get($key, 'INDENT', '  ', 'NEWLINE', "\n", 'SPACE', ' ', '$.path')` accepts
  the current formatting options before paths. The return value is still decoded
  PHP data; use `raw('JSON.GET', ...)` if formatted JSON text is needed.
  The obsolete `NOESCAPE` flag is no longer sent automatically.
- `numincrby()` and `nummultby()` accept integers and floats. Non-finite values,
  invalid UTF-8 and other JSON encoding errors fail before a command is sent.
- `arrindex()` sends `[start [stop]]` only when supplied. A stop without a start
  uses start `0`. Zero, negative indexes and the server's range rules are preserved.
- `arrappend()` and `arrinsert()` require at least one value. `mset()` validates
  every triplet and encodes every value before sending the command.

The existing convenience API is retained: default paths remain `.`, and a single
JSONPath match is unwrapped. Multiple matches remain arrays, including null entries.
`mget()` keeps its key-to-result map and does not unwrap JSONPath match arrays.
Legacy path results are preserved, including arrays and objects inside multi-path GET.

Behavior corrections that can affect existing callers:

- `set(..., [])` now stores `[]`. Use `new stdClass()` or `(object) []` to store `{}`.
- A conditional `set()` that is not applied returns null instead of an empty string.
- `objkeys()` returns the complete list of keys for a single JSONPath match,
  including an empty list for an empty object, instead of returning just its first key.
- Array commands preserve every match. Popped `0` and `false` values, zero lengths,
  empty key lists and empty JSONPath results are no longer discarded.

## Test and Development

You can use Docker Image provided by Redislabs.

```bash
docker run -p 6379:6379 redislabs/rejson:2.0.4
```


## PhpRedis Cluster

Use `RedisJson::createWithPhpRedisCluster()` with an authenticated `RedisCluster`
connection. The `ReJSON` alias also supports this factory. RedisJSON must be
available on every master. The routing fix requires `mkorkmaz/redislabs-common`
`2.0.0` or later in the 2.x series.

```php
use Redislabs\Module\RedisJson\RedisJson;

$cluster = new RedisCluster(
    null,
    ['127.0.0.1:7000', '127.0.0.1:7001', '127.0.0.1:7002'],
    2.0,
    2.0,
    false,
    getenv('REDIS_CLUSTER_PASSWORD') ?: null
);
$json = RedisJson::createWithPhpRedisCluster($cluster);
$json->set('{customer:42}:profile', '$', ['name' => 'Ada']);
$json->set('{customer:42}:settings', '$', ['theme' => 'dark']);
$profile = $json->get('{customer:42}:profile', '$');
$documents = $json->mget('{customer:42}:profile', '{customer:42}:settings', '$');
```

`mget()` and `mset()` require every key to be in the same hash slot. Use a shared hash tag,
as in the example. Neither method splits requests across slots; cross-slot reads
can fail or produce incomplete results depending on the server version.
Cross-slot `mset()` is rejected by Redis and does not write any of its values.
See the [Redis JSON.MGET documentation](https://redis.io/docs/latest/commands/json.mget/).
`debug('MEMORY', $key)` routes to the key's master, while `debug('HELP')` runs on
one node. Native methods remain available through `getClient()`.

### Cluster integration tests

Run against a three-master cluster with the standard equal slot allocation and
RedisJSON enabled. Tests use unique keys and delete only their own keys.
Set `REDIS_CLUSTER_PASSWORD` in the environment when authentication is required.

```sh
REDIS_CLUSTER_SEEDS=127.0.0.1:7000,127.0.0.1:7001,127.0.0.1:7002 \
    vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Module/RedisClusterTest.php
```

Without `REDIS_CLUSTER_SEEDS`, these integration tests are skipped.

### Testing the two repositories before release

The common `2.0.0` release must include the routing fix before this dependency
update is published. To test both local repositories through Composer first,
create a temporary directory beside `common` and `rejson`, and put this in its
`composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../common",
            "options": {"versions": {"mkorkmaz/redislabs-common": "2.0.0"}}
        },
        {
            "type": "path",
            "url": "../rejson",
            "options": {"versions": {"mkorkmaz/redislabs-rejson": "1.0.0"}}
        }
    ],
    "require": {"mkorkmaz/redislabs-rejson": "*"}
}
```

Run `composer install --no-dev` in that directory. The versions above are local
test aliases, not published releases. With development dependencies already
installed in `rejson`, run from the temporary directory:

```sh
REDIS_CLUSTER_SEEDS=127.0.0.1:7000 \
    ../rejson/vendor/bin/phpunit --bootstrap vendor/autoload.php \
    ../rejson/tests/Module/RedisClusterTest.php
```

This verifies the Composer package boundary without editing vendor source files.


## PHP 8.5 migration

PHP 8.5 or later in the 8.x series is required (`^8.5`). Development tools and
CI now target PHP 8.5. This is a breaking release; publish it as a new major
version. The `common` package must be released as `2.0.0` before the corresponding
`rejson` release.

Command properties now have native types: `string $command`, `array $arguments`,
and `?Closure $responseCallback`. Custom command subclasses must use compatible
property types. Convert callable arrays or strings with `Closure::fromCallable()`
before assigning a response callback. Module client references are readonly;
create a new module instance to replace its connection.

The refactor uses PHP 8.5 property `#[Override]` checks, first-class callables in
property defaults, and `clone($object, $properties)` for debug command copies.
See the [PHP 8.5 migration guide](https://www.php.net/manual/en/migration85.new-features.php).

Codeception loads the repository's `.env` file before running tests. Copy the
tracked example on a new checkout, then set the passwords locally:

```sh
cp .env.example .env
vendor/bin/codecept run unit --coverage
```

`.env` is ignored by Git. Existing environment variables override values from
`.env`. Both PhpRedis and Predis use `REDIS_HOST`, `REDIS_PORT`, and
`REDIS_PASSWORD`; defaults are `127.0.0.1:6379` with no password. Cluster tests
use `REDIS_CLUSTER_SEEDS` and `REDIS_CLUSTER_PASSWORD`. Leave seeds empty to
skip cluster tests. A failed connection remains a test failure, not a skipped test.

All integration tests use unique keys and delete only their own keys. They do
not use `FLUSHALL` or `FLUSHDB`. To target a separate test instance:

```sh
REDIS_PORT=16379 vendor/bin/codecept run unit --coverage
```

For local development with the sibling `common` repository, generate an ignored
Composer manifest from this repository's `composer.json`:

```sh
php -r '$config = json_decode(file_get_contents("composer.json"), true, flags: JSON_THROW_ON_ERROR); $config["repositories"] = [["type" => "path", "url" => "../common", "options" => ["versions" => ["mkorkmaz/redislabs-common" => "2.0.0"]]]]; file_put_contents("composer.local.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));'
COMPOSER=composer.local.json composer update
```

This installs development tools and symlinks the local package through Composer.
Use the same `COMPOSER` environment variable for subsequent local dependency updates.

`createWithPredis()` now requires `Predis\Client` (including subclasses), because
it uses `executeRaw()`, which is not part of `Predis\ClientInterface`. For a custom
transport, implement `Redislabs\Interfaces\RedisClientInterface` and pass that
adapter to the module constructor.
