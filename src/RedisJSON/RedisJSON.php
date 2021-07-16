<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJSON;

use Redislabs\Module\ModuleTrait;
use Redislabs\Module\RedisJSON\Command\Delete;
use Redislabs\Module\RedisJSON\Command\Get;
use Redislabs\Module\RedisJSON\Command\GetArray;
use Redislabs\Module\RedisJSON\Command\Set;
use Redislabs\Module\RedisJSON\Command\MultipleGet;
use Redislabs\Module\RedisJSON\Command\MultipleGetArray;
use Redislabs\Module\RedisJSON\Command\Type;
use Redislabs\Module\RedisJSON\Command\NumberIncrementBy;
use Redislabs\Module\RedisJSON\Command\NumberMultiplyBy;
use Redislabs\Module\RedisJSON\Command\StringAppend;
use Redislabs\Module\RedisJSON\Command\StringLength;
use Redislabs\Module\RedisJSON\Command\ArrayAppend;
use Redislabs\Module\RedisJSON\Command\ArrayIndex;
use Redislabs\Module\RedisJSON\Command\ArrayInsert;
use Redislabs\Module\RedisJSON\Command\ArrayLength;
use Redislabs\Module\RedisJSON\Command\ArrayPop;
use Redislabs\Module\RedisJSON\Command\ArrayTrim;
use Redislabs\Module\RedisJSON\Command\ObjectKeys;
use Redislabs\Module\RedisJSON\Command\ObjectLength;
use Redislabs\Module\RedisJSON\Command\Debug;
use Redislabs\Module\RedisJSON\Command\Forget;
use Redislabs\Module\RedisJSON\Command\Resp;

class RedisJSON implements RedisJsonInterface
{
    use ModuleTrait;

    protected static $moduleName = 'ReJSON';

    public function del(string $key, ?string $path = '.'): int
    {
        return $this->runCommand(
            Delete::createCommandWithArguments($key, $path)
        );
    }

    public function forget(string $key, ?string $path = '.'): int
    {
        return $this->del($key, $path);
    }

    public function set(string $key, string $path, $json, ?string $existentialModifier = null)
    {
        return $this->runCommand(
            Set::createCommandWithArguments($key, $path, $json, $existentialModifier)
        );
    }

    public function get(string $key, $paths = null)
    {
        return $this->runCommand(
            Get::createCommandWithArguments($key, $paths)
        );
    }

    public function getArray(string $key, $paths = null)
    {
        return $this->runCommand(
            GetArray::createCommandWithArguments($key, $paths)
        );
    }
    public function mget(...$arguments)
    {
        return $this->runCommand(
            MultipleGet::createCommandWithArguments($arguments)
        );
    }

    public function mgetArray(...$arguments)
    {
        return $this->runCommand(
            MultipleGetArray::createCommandWithArguments($arguments)
        );
    }
    public function type(string $key, ?string $paths = '.')
    {
        return $this->runCommand(
            Type::createCommandWithArguments($key, $paths)
        );
    }

    public function numincrby(string $key, string $path, int $incrementBy)
    {
        return $this->runCommand(
            NumberIncrementBy::createCommandWithArguments($key, $path, $incrementBy)
        );
    }

    public function nummultby(string $key, string $path, int $multiplyBy)
    {
        return $this->runCommand(
            NumberMultiplyBy::createCommandWithArguments($key, $path, $multiplyBy)
        );
    }

    public function strappend(string $key, $json, ?string $path = '.')
    {
        return $this->runCommand(
            StringAppend::createCommandWithArguments($key, $path, $json)
        );
    }

    public function strlen(string $key, ?string $path = '.')
    {
        return $this->runCommand(
            StringLength::createCommandWithArguments($key, $path)
        );
    }

    public function arrappend(string $key, string $path, ...$jsons)
    {
        return $this->runCommand(
            ArrayAppend::createCommandWithArguments($key, $path, $jsons)
        );
    }

    public function arrindex(string $key, string $path, $json, ?int $start = 0, ?int $stop = 0)
    {
        return $this->runCommand(
            ArrayIndex::createCommandWithArguments($key, $path, $json, $start, $stop)
        );
    }

    public function arrinsert(string $key, string $path, int $index, ...$jsons)
    {
        return $this->runCommand(
            ArrayInsert::createCommandWithArguments($key, $path, $index, $jsons)
        );
    }

    public function arrlen(string $key, string $path = '.')
    {
        return $this->runCommand(
            ArrayLength::createCommandWithArguments($key, $path)
        );
    }

    public function arrpop(string $key, ?string $path = '.', ?int $index = -1)
    {
        return $this->runCommand(
            ArrayPop::createCommandWithArguments($key, $path, $index)
        );
    }

    public function arrtrim(string $key, $path, ?int $start = 0, ?int $stop = 0)
    {
        return $this->runCommand(
            ArrayTrim::createCommandWithArguments($key, $path, $start, $stop)
        );
    }

    public function objkeys(string $key, ?string $path = '.')
    {
        return $this->runCommand(
            ObjectKeys::createCommandWithArguments($key, $path)
        );
    }

    public function objlen(string $key, ?string $path = '.')
    {
        return $this->runCommand(
            ObjectLength::createCommandWithArguments($key, $path)
        );
    }

    public function debug(string $subcommand, ?string $key = null, ?string $path = '.')
    {
        return $this->runCommand(
            Debug::createCommandWithArguments($subcommand, $key, $path)
        );
    }

    public function resp(string $key, ?string $paths = '.')
    {
        return $this->runCommand(
            Resp::createCommandWithArguments($key, $paths)
        );
    }
}
