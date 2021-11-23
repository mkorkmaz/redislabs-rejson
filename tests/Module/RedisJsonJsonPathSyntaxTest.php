<?php

declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use Predis;
use Redislabs\Module\ReJSON\ReJSON;
use Redislabs\Module\RedisJson\RedisJson;
use RedislabsModulesTest\UnitTester;

class RedisJsonJsonPathSyntaxTest extends \Codeception\Test\Unit
{
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
        $this->redisClient = new Predis\Client();
        $this->reJsonModule = ReJSON::createWithPredis($this->redisClient);
    }

    protected function _after()
    {
        $this->redisClient->flushall();
    }
    // phpcs:enable

    /**
     * @test
     */
    public function shouldRunReJSONCommandSetAndGetAndTypeSuccessfully(): void
    {
        $result = $this->reJsonModule->set('test', '$', [], 'NX');
        $this->assertEquals('OK', $result, 'JSON.SET sets new value');

        $result = $this->reJsonModule->set('test', '$', ['failed' => 'set'], 'NX');
        $this->assertEquals(null, $result, 'JSON.SET sets .failed value for XX modifier');
        $result = $this->reJsonModule->set('test', '$', ['foo' => 'bar'], 'XX');
        $this->assertEquals('OK', $result, 'JSON.SET sets .foo value');
        $result = $this->reJsonModule->set('test', '$.baz', 'qux');
        $this->assertEquals('OK', $result, 'JSON.SET sets .baz:qux value');
        $result = $this->reJsonModule->set('test', '$.baz', 'quux', 'XX');
        $this->assertEquals('OK', $result, 'JSON.SET sets existing .foo.baz value');
        $root = $this->reJsonModule->get('test');
        $this->assertEquals(
            'quux',
            $root['baz'],
            'JSON.GET . and check for root element foo has correct value'
        );
        $baz = $this->reJsonModule->get('test', '$.baz');
        $this->assertEquals(
            'quux',
            $baz,
            'JSON.GET .baz and check for baz element has correct value'
        );
        $singleLegacyPath = $this->reJsonModule->get('test', '.foo');
        $this->assertEquals(
            'bar',
            $singleLegacyPath,
            'JSON.GET . foo and check for foo has correct value'
        );
        $singleLegacyJsonPath = $this->reJsonModule->get('test', '$.foo');
        $this->assertEquals(
            'bar',
            $singleLegacyJsonPath,
            'JSON.GET . foo and check for foo has correct value'
        );

        $this->reJsonModule->set('test', '$.num', 1);
        $this->assertEquals('integer', $this->reJsonModule->type('test', '$.num'));
        $this->reJsonModule->set('doc', '$', json_decode('{"a":2, "nested": {"a": true}, "foo": "bar"}', true));
        $this->assertEquals(['integer', 'boolean'], $this->reJsonModule->type('doc', '$..a'));
        $this->reJsonModule->set('doc', '$', json_decode('{"a":2, "b": 3, "nested": {"a": 4, "b": null}}', true));
        $this->assertEquals([3, null], $this->reJsonModule->get('doc', '$..b'));
        $this->assertEquals(['..a' => [2, 4], '$..b' => [3, null]], $this->reJsonModule->get('doc', '..a', '$..b'));
    }

    /**
     * @test
     */
    public function shouldRunReJSONCommandSetAndGetAndTypeSuccessfullyForMultiple(): void
    {
        $data = require __DIR__ . '/sample/data.php';
        $result = $this->reJsonModule->set('doc', '$', $data, 'NX');
        $this->assertEquals('OK', $result, 'JSON.SET sets new value');

        $doc = $this->reJsonModule->get('doc');
        $this->assertEquals(
            '0001',
            $doc['id'],
            'JSON.GET . and check for root element foo has correct value'
        );
        $partial = $this->reJsonModule->get('doc', '.id', '$.batters');
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


    /**
     * @test
     */
    public function shouldRunReJSONCommandDelAndForgetSuccessfully(): void
    {
        $result = $this->reJsonModule->set(
            'test',
            '$',
            ['foo' => 'bar', 'baz' => 'qux', 'quux' => ['quuz' => 'corge']],
            'NX'
        );
        $this->assertEquals('OK', $result, 'JSON.SET sets new value');
        $root = $this->reJsonModule->get('test', '$');
        $this->assertEquals('bar', $root['foo']);
        $this->assertEquals(1, $this->reJsonModule
            ->del('test', $path = '$.foo'), 'Trying to delete .foo');
        $this->assertEquals(1, $this->reJsonModule
            ->forget('test', $path = '$.quux.quuz'), 'Trying to delete .baz.quux');
        $root = $this->reJsonModule->get('test', '$');
        $this->assertEquals('qux', $root['baz']);
        $this->assertEquals(1, $this->reJsonModule->del('test', $path = '$'), 'Trying to delete root');
    }

    /**
     * @test
     */
    public function shouldFailForReJSONCommandMgetWhenNumberOfArgumentsAreInsufficient(): void
    {
        $this->expectException(\Redislabs\Exceptions\InvalidNumberOfArgumentsException::class);
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->mget('.foo');
    }

    /**
     * @test
     */
    public function shouldRunReJSONCommandMgetSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test1', '$', ['foo' => 'baz']);
        $this->reJsonModule->set('test2', '$', ['foo' => 'bar']);
        $this->reJsonModule->set('test3', '$', ['foo' => 'qux']);
        $this->reJsonModule->set('test4', '$', []);
        $this->reJsonModule->set('test5', '$', ['foo' => ['bar' => 'baz']]);
        $mgetResult = $this->reJsonModule->mget('test1', 'test2', 'test3', 'test4', '$.foo');
        $this->assertEquals(
            ['baz'],
            $mgetResult['test1'],
            'test1.foo = baz'
        );
        $this->assertEquals(
            ['bar'],
            $mgetResult['test2'],
            'test2.foo = bar'
        );
        $this->assertEquals(
            ['qux'],
            $mgetResult['test3'],
            'test3.foo = quuz'
        );
        $this->assertEquals(
            [],
            $mgetResult['test4'],
            'test4.foo is null'
        );

        $this->reJsonModule->set('doc1', '$', json_decode('{"a":1, "b": 2, "nested": {"a": 3}, "c": null}', true));
        $this->reJsonModule->set('doc2', '$', json_decode('{"a":4, "b": 5, "nested": {"a": 6}, "c": null}', true));
        $this->assertSame(['doc1' => [1, 3], 'doc2' => [4, 6]], $this->reJsonModule->mget('doc1', 'doc2', '$..a'));
    }

    /**
     * @test
     */
    public function shouldRunReJSONNumCommandsSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set(
            'numDoc',
            '$',
            json_decode('{"a":"b","b":[{"a":2}, {"a":5}, {"a":"c"}], "d": 2}', true, 512, JSON_THROW_ON_ERROR)
        );
        $value = $this->reJsonModule->get('numDoc', '$.d');
        $this->assertEquals(2, $value);
        $incrementedValue = $this->reJsonModule->numincrby('numDoc', '$.d', 4);
        $this->assertEquals($value + 4, $incrementedValue);
        $incrementedNonIntegerValue = $this->reJsonModule->numincrby('numDoc', '$.a', 4);
        $this->assertEquals(null, $incrementedNonIntegerValue);
        $incrementedMultipleValue = $this->reJsonModule->numincrby('numDoc', '$..a', 2);
        $this->assertEquals([null, 4, 7, null], $incrementedMultipleValue);
        $multipliedValue = $this->reJsonModule->nummultby('numDoc', '$.d', 2);
        $this->assertEquals($incrementedValue * 2, $multipliedValue);
    }

    /**
     * @test
     */
    public function shouldRunReJSONStringCommandsSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test', '$', ['foo' => 'bar']);
        $appendedStringsLength = $this->reJsonModule->strappend('test', 'baz', '$.foo');
        $this->assertEquals(6, $appendedStringsLength);
        $this->assertEquals('barbaz', $this->reJsonModule->get('test', '$.foo'));
        $this->assertEquals(strlen('barbaz'), $this->reJsonModule->strlen('test', '$.foo'));
    }

    /**
     * @test
     */
    public function shouldRunReJSONArrayCommandsSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test', '$', ['foo', 'bar']);
        $appendedArraysLength = $this->reJsonModule->arrappend('test', '$', 'baz', 'qux');
        $this->assertEquals(2 + 2, $appendedArraysLength);
        $this->assertEquals(1, $this->reJsonModule->arrindex('test', '$', 'bar'));
        $this->assertEquals(-1, $this->reJsonModule->arrindex('test', '$', 'quuz'));
        $arrayNewSize = $this->reJsonModule->arrinsert('test', '$', 2, 'quuz', 'quux');
        $this->assertEquals(6, $arrayNewSize);
        $this->assertEquals($arrayNewSize, $this->reJsonModule->arrlen('test', '$'));
        $this->assertEquals('qux', $this->reJsonModule->arrpop('test', '$', 5));
        $this->assertEquals($arrayNewSize - 1, $this->reJsonModule->arrlen('test', '$'));
        $this->assertEquals(3, $this->reJsonModule->arrtrim('test', '$', 1, 3));
        $this->assertEquals('quux', $this->reJsonModule->arrpop('test'));
    }

    /**
     * @test
     */
    public function shouldRunReJSONObjectCommandsSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set(
            'test',
            '$',
            json_decode('{"a":[3],"d":{"e":"value"}, "nested": {"a": {"b":2, "c": 1}}}', true)
        );
        $this->assertEquals([null, ['b', 'c']], $this->reJsonModule->objkeys('test', '$..a'));
        $this->assertEquals('e', $this->reJsonModule->objkeys('test', '$.d'));

        $this->assertEquals(1, $this->reJsonModule->objlen('test', '$.d'));

        $this->assertEquals([null, 2], $this->reJsonModule->objlen('test', '$..a'));
    }


    /**
     * @test
     */
    public function shouldRunReJSONDebugCommandsSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $help = $this->reJsonModule->debug('HELP');
        $this->assertStringContainsString('HELP', $help[1]);
        $this->reJsonModule->set('debug-test', '$', ['foo', 'bar']);
        $memory = $this->reJsonModule->debug('MEMORY', 'debug-test', '$');
        $this->assertEquals(24, $memory);
    }

    /**
     * @test
     */
    public function shouldFailForInvalidDebugCommand(): void
    {
        $this->expectException(\Redislabs\Module\RedisJSON\Exceptions\InvalidDebugSubcommandException::class);
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->debug('LS');
    }

    /**
     * @test
     */
    public function shouldRunReJSONRespCommandSuccessfully(): void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test', '$', ['foo' => 'bar', 'baz' => 'quz']);
        $resp = $this->reJsonModule->resp('test');
        $this->assertEquals(['foo', 'bar'], [$resp[1], $resp[2]]);
        $this->assertEquals(['baz', 'quz'], [$resp[3], $resp[4]]);
        $this->assertCount(5, $resp);
    }
}
