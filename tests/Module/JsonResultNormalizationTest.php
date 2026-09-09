<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use PHPUnit\Framework\TestCase;
use Redislabs\Module\RedisJson\Path;
use Redislabs\Module\RedisJson\RedisJson;

final class JsonResultNormalizationTest extends TestCase
{
    public function testMissingArrayResponsesBecomeNull(): void
    {
        foreach ([null, false] as $response) {
            self::assertNull(RedisJson::getArrayResult($response, [new Path('$')]));
            self::assertNull(RedisJson::getArrayResult($response, [new Path('.')]));
        }
    }

    public function testArrayResponseRequiresExactlyOnePath(): void
    {
        self::assertNull(RedisJson::getArrayResult(['1'], []));
        self::assertNull(RedisJson::getArrayResult(['1'], [new Path('$.a'), new Path('$.b')]));
    }

    public function testMissingPathResponsesDifferFromZeroAndEmptyResults(): void
    {
        foreach (['$', '.'] as $path) {
            self::assertNull(RedisJson::getPathResult(false, new Path($path)));
            self::assertSame(0, RedisJson::getPathResult(0, new Path($path)));
            self::assertSame([], RedisJson::getPathResult([], new Path($path)));
        }
    }

    public function testMissingNumericResponsesDifferFromZero(): void
    {
        self::assertNull(RedisJson::getNumResult(false));
        self::assertNull(RedisJson::getNumResult(null));
        self::assertSame(0, RedisJson::getNumResult('0'));
        self::assertSame(0.0, RedisJson::getNumResult('[0.0]'));
    }
}
