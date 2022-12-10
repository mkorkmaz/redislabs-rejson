<?php

declare(strict_types=1);

namespace Redislabs\Module\RedisJson;

use Redislabs\Interfaces\RedisClientInterface;
use Redislabs\Module\ModuleTrait;
use Redislabs\Module\RedisJson\Command\Delete;
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
use Redislabs\Module\RedisJson\Exceptions\RedisJsonModuleNotFound;
use Redislabs\Module\RedisJson\Exceptions\RedisJsonModuleVersionNotSupported;

class RedisJson implements RedisJsonInterface
{
    use ModuleTrait;

    private array $moduleVersion;

    protected static $moduleName = 'ReJSON';

    public function __construct(RedisClientInterface $redisClient)
    {
        $this->setModuleVersion($redisClient->rawCommand('MODULE', ['LIST']));
        if ($this->moduleVersion['major'] < 2) {
            throw new RedisJsonModuleVersionNotSupported(
                sprintf(
                    'This library does not support RedisJSON Module version lower than 2. You use %d',
                    $this->moduleVersion['major']
                )
            );
        }
        $this->redisClient = $redisClient;
    }

    private function setModuleVersion(array $modules): void
    {
        $moduleData = array_values(
            array_filter($modules, static fn($module) => $module[1] === self::$moduleName)
        );
        if (count($moduleData) === 0) {
            throw new RedisJsonModuleNotFound(
                'You need to have Redis ReJSON module to use this library. Please check https://oss.redis.com/redisjson'
            );
        }
        $redisModuleVersionMajor = floor($moduleData[0][3] / 10000);
        $redisModuleVersionMinor = floor(($moduleData[0][3] - $redisModuleVersionMajor * 10000) / 100);
        $redisModuleVersionPatch = $moduleData[0][3] - $redisModuleVersionMajor * 10000
            - $redisModuleVersionMinor * 100;
        $this->moduleVersion = [
            'version' => $moduleData[0][3],
            'major' => $redisModuleVersionMajor,
            'minor' => $redisModuleVersionMinor,
            'patch' => $redisModuleVersionPatch,
            'semver' => implode('.', [
                $redisModuleVersionMajor,
                $redisModuleVersionMinor,
                $redisModuleVersionPatch
            ]),
        ];
    }

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
        $setCommandResult = $this->runCommand(
            Set::createCommandWithArguments($key, $path, $json, $existentialModifier)
        );
        if ($setCommandResult !== true && $setCommandResult !== 'OK') {
            return "";
        }
        return 'OK';
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

    /**
     * @param Path[] $paths
     * @return mixed
     * @throws \JsonException
     */
    public static function getResult(string $result, array $paths)
    {
        $result = json_decode($result, (bool) JSON_OBJECT_AS_ARRAY, 512, JSON_THROW_ON_ERROR);
        if (count($paths) === 1 && $paths[0]->isLegacyPath() === false) {
            return (is_countable($result) ? count($result) : 0) === 1 ? $result[0] : $result;
        }
        if (count($paths) > 1) {
            $resultArray = [];
            foreach ($result as $itemKey => $itemValue) {
                $resultArray[$itemKey] = (is_countable($itemValue) ? count($itemValue) : 0) === 1 ? $itemValue[0]
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
    public static function getArrayResult(mixed $result, array $paths)
    {
        if (!empty($result) && count($paths) === 1) {
            if ($paths[0]->isLegacyPath() === false) {
                return (is_string($result[0])) ? json_decode($result[0], true, 512, JSON_THROW_ON_ERROR) :  $result[0];
            }
            return (is_string($result)) ? json_decode($result, true, 512, JSON_THROW_ON_ERROR) :  $result;
        }

        return null;
    }

    /**
     * @return mixed
     * @throws \JsonException
     */
    public static function getNumResult(mixed $result)
    {
        $result = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        if (is_countable($result) && count($result) === 1) {
            return $result[0];
        }
        return $result;
    }
}
