<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use RedislabsModulesTest\Support\RedisConnection;
use RedislabsModulesTest\Support\RedisTestKeys;
use Redis;
use Redislabs\Module\ReJSON\ReJSON;
use Redislabs\Module\RedisJson\RedisJson;
use RedislabsModulesTest\UnitTester;

class PhpRedisTest extends \Codeception\Test\Unit
{
    use RedisTestKeys;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var ReJSON
     */
    protected $reJsonModule;
    /**
     * @var Predis\Client
     */
    private $redisClient;
    // phpcs:disable
    protected function _before()
    {
        $this->redisClient = RedisConnection::phpRedis();
        $this->reJsonModule = ReJSON::createWithPhpRedis($this->redisClient);
    }

    protected function _after()
    {
        if ($this->redisClient !== null) {
            $this->deleteTestKeys($this->redisClient);
        }
    }
    // phpcs:enable

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldRunReJSONCommandSetAndGetAndTypeSuccessfully(): void
    {
        $result = $this->reJsonModule->set($this->key('test'), '$', [], 'NX');
        $this->assertEquals('OK', $result, 'JSON.SET sets new value');

        $result = $this->reJsonModule->set($this->key('test'), '$', ['failed' => 'set'], 'NX');
        $this->assertEquals(null, $result, 'JSON.SET sets .failed value for XX modifier');
        $result = $this->reJsonModule->set($this->key('test'), '$', ['foo' => 'bar'], 'XX');
        $this->assertEquals('OK', $result, 'JSON.SET sets .foo value');
        $result = $this->reJsonModule->set($this->key('test'), '$.baz', 'qux');
        $this->assertEquals('OK', $result, 'JSON.SET sets .baz:qux value');
        $result = $this->reJsonModule->set($this->key('test'), '$.baz', 'quux', 'XX');
        $this->assertEquals('OK', $result, 'JSON.SET sets existing .foo.baz value');
        $root = $this->reJsonModule->get($this->key('test'));
        $this->assertEquals(
            'quux',
            $root['baz'],
            'JSON.GET . and check for root element foo has correct value'
        );
        $baz = $this->reJsonModule->get($this->key('test'), '$.baz');
        $this->assertEquals(
            'quux',
            $baz,
            'JSON.GET .baz and check for baz element has correct value'
        );
        $singleLegacyPath = $this->reJsonModule->get($this->key('test'), '.foo');
        $this->assertEquals(
            'bar',
            $singleLegacyPath,
            'JSON.GET . foo and check for foo has correct value'
        );
        $singleLegacyJsonPath = $this->reJsonModule->get($this->key('test'), '$.foo');
        $this->assertEquals(
            'bar',
            $singleLegacyJsonPath,
            'JSON.GET . foo and check for foo has correct value'
        );

        $this->reJsonModule->set($this->key('test'), '$.num', 1);
        $this->assertEquals('integer', $this->reJsonModule->type($this->key('test'), '$.num'));
        $this->reJsonModule->set(
            $this->key('doc'),
            '$',
            json_decode('{"a":2, "nested": {"a": true}, "foo": "bar"}', true)
        );
        $this->assertEquals(['integer', 'boolean'], $this->reJsonModule->type($this->key('doc'), '$..a'));
        $this->reJsonModule->set(
            $this->key('doc'),
            '$',
            json_decode('{"a":2, "b": 3, "nested": {"a": 4, "b": null}}', true)
        );
        $this->assertEquals([3, null], $this->reJsonModule->get($this->key('doc'), '$..b'));
        $this->assertEquals(
            ['..a' => [2, 4], '$..b' => [3, null]],
            $this->reJsonModule->get($this->key('doc'), '..a', '$..b')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldRunReJSONCommandSetAndGetAndTypeSuccessfullyForMultiple(): void
    {
        $data = require __DIR__ . '/sample/data.php';
        $result = $this->reJsonModule->set($this->key('doc'), '$', $data, 'NX');
        $this->assertEquals('OK', $result, 'JSON.SET sets new value');

        $doc = $this->reJsonModule->get($this->key('doc'));
        $this->assertEquals(
            '0001',
            $doc['id'],
            'JSON.GET . and check for root element foo has correct value'
        );
        $partial = $this->reJsonModule->get($this->key('doc'), '.id', '$.batters');
        $this->assertEquals(
            '0001',
            $partial['.id'],
            'JSON.GET ..a $..b and check for result element foo has correct values'
        );
        $this->assertEquals(
            '1001',
            $partial['$.batters']['batter'][0]['id'],
            'JSON.GET ..a $..b and check for result element foo has correct values'
        );
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldRunReJSONCommandDelAndForgetSuccessfully(): void
    {
        $result = $this->reJsonModule->set(
            $this->key('test'),
            '$',
            ['foo' => 'bar', 'baz' => 'qux', 'quux' => ['quuz' => 'corge']],
            'NX'
        );
        $this->assertEquals('OK', $result, 'JSON.SET sets new value');
        $root = $this->reJsonModule->get($this->key('test'), '$');
        $this->assertEquals('bar', $root['foo']);
        $this->assertEquals(1, $this->reJsonModule
            ->del($this->key('test'), $path = '$.foo'), 'Trying to delete .foo');
        $this->assertEquals(1, $this->reJsonModule
            ->forget($this->key('test'), $path = '$.quux.quuz'), 'Trying to delete .baz.quux');
        $root = $this->reJsonModule->get($this->key('test'), '$');
        $this->assertEquals('qux', $root['baz']);
        $this->assertEquals(1, $this->reJsonModule->del($this->key('test'), $path = '$'), 'Trying to delete root');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldFailForReJSONCommandMgetWhenNumberOfArgumentsAreInsufficient(): void
    {
        $this->expectException(\Redislabs\Exceptions\InvalidNumberOfArgumentsException::class);
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->mget('.foo');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldRunReJSONCommandMgetSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set($this->key('test1'), '$', ['foo' => 'baz']);
        $this->reJsonModule->set($this->key('test2'), '$', ['foo' => 'bar']);
        $this->reJsonModule->set($this->key('test3'), '$', ['foo' => 'qux']);
        $this->reJsonModule->set($this->key('test4'), '$', []);
        $this->reJsonModule->set($this->key('test5'), '$', ['foo' => ['bar' => 'baz']]);
        $mgetResult = $this->reJsonModule->mget(
            $this->key('test1'),
            $this->key('test2'),
            $this->key('test3'),
            $this->key('test4'),
            '$.foo'
        );
        $this->assertEquals(
            ['baz'],
            $mgetResult[$this->key('test1')],
            'test1.foo = baz'
        );
        $this->assertEquals(
            ['bar'],
            $mgetResult[$this->key('test2')],
            'test2.foo = bar'
        );
        $this->assertEquals(
            ['qux'],
            $mgetResult[$this->key('test3')],
            'test3.foo = quuz'
        );
        $this->assertEquals(
            [],
            $mgetResult[$this->key('test4')],
            'test4.foo is null'
        );

        $this->reJsonModule->set(
            $this->key('doc1'),
            '$',
            json_decode('{"a":1, "b": 2, "nested": {"a": 3}, "c": null}', true)
        );
        $this->reJsonModule->set(
            $this->key('doc2'),
            '$',
            json_decode('{"a":4, "b": 5, "nested": {"a": 6}, "c": null}', true)
        );
        $this->assertSame(
            [$this->key('doc1') => [1, 3], $this->key('doc2') => [4, 6]],
            $this->reJsonModule->mget($this->key('doc1'), $this->key('doc2'), '$..a')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldRunReJSONNumCommandsSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set(
            $this->key('numDoc'),
            '$',
            json_decode('{"a":"b","b":[{"a":2}, {"a":5}, {"a":"c"}], "d": 2}', true, 512, JSON_THROW_ON_ERROR)
        );
        $value = $this->reJsonModule->get($this->key('numDoc'), '$.d');
        $this->assertEquals(2, $value);
        $incrementedValue = $this->reJsonModule->numincrby($this->key('numDoc'), '$.d', 4);
        $this->assertEquals($value + 4, $incrementedValue);
        $incrementedNonIntegerValue = $this->reJsonModule->numincrby($this->key('numDoc'), '$.a', 4);
        $this->assertEquals(null, $incrementedNonIntegerValue);
        $incrementedMultipleValue = $this->reJsonModule->numincrby($this->key('numDoc'), '$..a', 2);
        $this->assertEquals([null, 4, 7, null], $incrementedMultipleValue);
        $multipliedValue = $this->reJsonModule->nummultby($this->key('numDoc'), '$.d', 2);
        $this->assertEquals($incrementedValue * 2, $multipliedValue);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldRunReJSONStringCommandsSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set($this->key('test'), '$', ['foo' => 'bar']);
        $appendedStringsLength = $this->reJsonModule->strappend($this->key('test'), 'baz', '$.foo');
        $this->assertEquals(6, $appendedStringsLength);
        $this->assertEquals('barbaz', $this->reJsonModule->get($this->key('test'), '$.foo'));
        $this->assertEquals(strlen('barbaz'), $this->reJsonModule->strlen($this->key('test'), '$.foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldRunReJSONArrayCommandsSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set($this->key('test'), '$', ['foo', 'bar']);
        $appendedArraysLength = $this->reJsonModule->arrappend($this->key('test'), '$', 'baz', 'qux');
        $this->assertEquals(2 + 2, $appendedArraysLength);
        $this->assertEquals(1, $this->reJsonModule->arrindex($this->key('test'), '$', 'bar'));
        $this->assertEquals(-1, $this->reJsonModule->arrindex($this->key('test'), '$', 'quuz'));
        $arrayNewSize = $this->reJsonModule->arrinsert($this->key('test'), '$', 2, 'quuz', 'quux');
        $this->assertEquals(6, $arrayNewSize);
        $this->assertEquals($arrayNewSize, $this->reJsonModule->arrlen($this->key('test'), '$'));
        $this->assertEquals('qux', $this->reJsonModule->arrpop($this->key('test'), '$', 5));
        $this->assertEquals($arrayNewSize - 1, $this->reJsonModule->arrlen($this->key('test'), '$'));
        $this->assertEquals(3, $this->reJsonModule->arrtrim($this->key('test'), '$', 1, 3));
        $this->assertEquals('quux', $this->reJsonModule->arrpop($this->key('test')));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldRunReJSONObjectCommandsSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set(
            $this->key('test'),
            '$',
            json_decode('{"a":[3],"d":{"e":"value"}, "nested": {"a": {"b":2, "c": 1}}}', true)
        );
        $this->assertEquals([null, ['b', 'c']], $this->reJsonModule->objkeys($this->key('test'), '$..a'));
        $this->assertEquals(['e'], $this->reJsonModule->objkeys($this->key('test'), '$.d'));

        $this->assertEquals(1, $this->reJsonModule->objlen($this->key('test'), '$.d'));

        $this->assertEquals([null, 2], $this->reJsonModule->objlen($this->key('test'), '$..a'));
    }




    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldFailForInvalidDebugCommand(): void
    {
        $this->expectException(\Redislabs\Module\RedisJson\Exceptions\InvalidDebugSubcommandException::class);
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->debug('LS');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shouldRunReJSONRespCommandSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set($this->key('test'), '$', ['foo' => 'bar', 'baz' => 'quz']);
        $resp = $this->reJsonModule->resp($this->key('test'));
        $this->assertEquals(['foo', 'bar'], [$resp[1], $resp[2]]);
        $this->assertEquals(['baz', 'quz'], [$resp[3], $resp[4]]);
        $this->assertCount(5, $resp);
    }
}
