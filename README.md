# RedisJSON-PHP: Redislabs RedisJSON aka ReJson Client for PHP

RedisJSON-PHP provides a client for Redislabs' ReJSON Module for PHP. This library supports both widely used redis clients ([PECL Redis Extension](https://github.com/phpredis/phpredis/#readme) and [Predis](https://github.com/nrk/predis)).  


[![Build Status](https://api.travis-ci.com/mkorkmaz/redislabs-rejson.svg?branch=master)](https://travis-ci.com/mkorkmaz/redislabs-rejson) [![Coverage Status](https://coveralls.io/repos/github/mkorkmaz/redislabs-rejson/badge.svg?branch=master)](https://coveralls.io/github/mkorkmaz/redislabs-rejson?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mkorkmaz/redislabs-rejson/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mkorkmaz/redislabs-rejson/?branch=master) [![Latest Stable Version](https://poser.pugx.org/mkorkmaz/redislabs-rejson/v/stable)](https://packagist.org/packages/mkorkmaz/redislabs-rejson) [![Total Downloads](https://poser.pugx.org/mkorkmaz/redislabs-rejson/downloads)](https://packagist.org/packages/mkorkmaz/redislabs-rejson) [![Latest Unstable Version](https://poser.pugx.org/mkorkmaz/redislabs-rejson/v/unstable)](https://packagist.org/packages/mkorkmaz/redislabs-rejson) [![License](https://poser.pugx.org/mkorkmaz/redislabs-rejson/license)](https://packagist.org/packages/mkorkmaz/redislabs-rejson)


## About RedisJSON

"RedisJSON is a Redis module that implements ECMA-404 The JSON Data Interchange Standard as a native data type. It allows storing, updating and fetching JSON values from Redis keys (documents)".

[More info about RedisJSON](https://oss.redislabs.com/redisjson/).


## RedisJSON-PHP Interface

Commanda are named after lowercase version of the original RedisJSON commands.

```php
<?php

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

```

## Installation

The recommended method to install RedisJSON-PHP is with composer.

```bash
composer require mkorkmaz/redislabs-rejson
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
$reJSON = ReJSON::createWithPhpRedis($redisClient);
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
- **$key (or $keys - array that containes $key items)** parameters are all string.
- **$json (or $jsons - array that containes $json items)** parameters can be any type of json encodable data (array, int, string, stdClass, any JsonSerializable object etc...). 
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
$array = $redisJson->getArray('test', '.');
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
$array = $redisJson->mgetArray('test', 'test2', '.');
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


## Test and Development

You can use Docker Image provided by Redislabs.

```bash
docker run -p 6379:6379 redislabs/rejson:latest
```
