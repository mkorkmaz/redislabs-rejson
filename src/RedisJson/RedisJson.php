<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson;

use Redislabs\Module\ModuleTrait;
use Redislabs\Module\RedisJson\Command\Delete;
use Redislabs\Module\RedisJson\Command\Clear;
use Redislabs\Module\RedisJson\Command\Merge;
use Redislabs\Module\RedisJson\Command\MultipleSet;
use Redislabs\Module\RedisJson\Command\Toggle;
use Redislabs\Module\RedisJson\Command\Get;
use Redislabs\Module\RedisJson\Command\Set;
use Redislabs\Module\RedisJson\Command\MultipleGet;
use Redislabs\Module\RedisJson\Command\Type;
use Redislabs\Module\RedisJson\Command\NumberIncrementBy;
use Redislabs\Module\RedisJson\Command\NumberMultiplyBy;
use Redislabs\Module\RedisJson\Command\StringAppend;
use Redislabs\Module\RedisJson\Command\StringLength;
use Redislabs\Module\RedisJson\Command\ArrayAppend;
use Redislabs\Module\RedisJson\Command\ArrayIndex;
use Redislabs\Module\RedisJson\Command\ArrayInsert;
use Redislabs\Module\RedisJson\Command\ArrayLength;
use Redislabs\Module\RedisJson\Command\ArrayPop;
use Redislabs\Module\RedisJson\Command\ArrayTrim;
use Redislabs\Module\RedisJson\Command\ObjectKeys;
use Redislabs\Module\RedisJson\Command\ObjectLength;
use Redislabs\Module\RedisJson\Command\Debug;
use Redislabs\Module\RedisJson\Command\Resp;

/**
 * @phpstan-consistent-constructor
 */
class RedisJson implements RedisJsonInterface
{
    use ModuleTrait;

    protected static string $moduleName = 'ReJSON';

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

    public function set(string $key, string $path, $json, ?string $existentialModifier = null): ?string
    {
        $setCommandResult = $this->runCommand(
            Set::createCommandWithArguments($key, $path, $json, $existentialModifier)
        );
        return self::getStatusResult($setCommandResult);
    }

    public function clear(string $key, string $path = '.'): int
    {
        return $this->runCommand(Clear::createCommandWithArguments($key, $path));
    }

    public function merge(string $key, string $path, mixed $json): ?string
    {
        return $this->runCommand(Merge::createCommandWithArguments($key, $path, $json));
    }

    public function mset(mixed ...$arguments): ?string
    {
        return $this->runCommand(MultipleSet::createCommandWithArguments($arguments));
    }

    public function toggle(string $key, string $path = '.'): bool|array|null
    {
        return $this->runCommand(Toggle::createCommandWithArguments($key, $path));
    }

    public function get(...$arguments)
    {
        return $this->runCommand(
            Get::createCommandWithArguments($arguments)
        );
    }

    public function mget(...$arguments)
    {
        return $this->runCommand(
            MultipleGet::createCommandWithArguments($arguments)
        );
    }

    public function type(string $key, ?string $paths = '.')
    {
        return $this->runCommand(
            Type::createCommandWithArguments($key, $paths)
        );
    }

    public function numincrby(string $key, string $path, int|float $incrementBy)
    {
        return $this->runCommand(
            NumberIncrementBy::createCommandWithArguments($key, $path, $incrementBy)
        );
    }

    /** @deprecated RedisJSON deprecated JSON.NUMMULTBY in version 2.0. */
    public function nummultby(string $key, string $path, int|float $multiplyBy)
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

    public function arrindex(string $key, string $path, $json, ?int $start = null, ?int $stop = null)
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

    /**
     * @param Path[] $paths
     * @return mixed
     * @throws \JsonException
     */
    public static function getResult(string $result, array $paths)
    {
        $result = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        if (count($paths) === 1 && $paths[0]->isLegacyPath() === false) {
            return (is_countable($result) ? count($result) : 0) === 1 ? $result[0] : $result;
        }
        if (count($paths) > 1 && array_any($paths, static fn(Path $path) => !$path->isLegacyPath())) {
            $resultArray = [];
            foreach ($result as $itemKey => $itemValue) {
                $resultArray[$itemKey] = is_array($itemValue) && count($itemValue) === 1 ? $itemValue[0]
                    : $itemValue;
            }
            return $resultArray;
        }
        return $result;
    }

    /**
     * @param Path[] $paths
     * @return mixed
     * @throws \JsonException
     */
    public static function getArrayResult(mixed $result, array $paths): mixed
    {
        if ($result === null || $result === false) {
            return null;
        }
        if (count($paths) !== 1) {
            return null;
        }
        if ($paths[0]->isLegacyPath()) {
            return is_string($result) ? json_decode($result, true, 512, JSON_THROW_ON_ERROR) : $result;
        }
        $values = array_map(
            static fn($value) => is_string($value)
                ? json_decode($value, true, 512, JSON_THROW_ON_ERROR)
                : ($value === false ? null : $value),
            $result
        );
        return count($values) === 1 ? $values[0] : $values;
    }

    public static function getPathResult(mixed $result, Path $path): mixed
    {
        if ($result === false) {
            return null;
        }
        if (is_array($result)) {
            $result = array_map(static fn($value) => $value === false ? null : $value, $result);
        }
        if (!$path->isLegacyPath() && is_array($result) && count($result) === 1) {
            return $result[0];
        }
        return $result;
    }

    public static function getStatusResult(mixed $result): ?string
    {
        return $result === true || $result === 'OK' ? 'OK' : null;
    }

    /**
     * @return mixed
     * @throws \JsonException
     */
    public static function getNumResult(mixed $result)
    {
        $result = is_string($result) ? json_decode($result, true, 512, JSON_THROW_ON_ERROR) : $result;
        if ($result === false) {
            return null;
        }
        if (is_countable($result) && count($result) === 1) {
            return $result[0];
        }
        return $result;
    }
}
