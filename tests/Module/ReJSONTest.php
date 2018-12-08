<?php
declare(strict_types=1);

namespace RedislabsModulesTest\Module;

use Predis;
use Redislabs\Module\ReJSON\ReJSON;

class ReJSONTest extends \Codeception\Test\Unit
{
    /**
     * @var \RedislabsModulesTest\UnitTester
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

    protected function _before()
    {
        $this->redisClient = new Predis\Client();
        $this->reJsonModule = ReJSON::createWithPredis($this->redisClient);
    }

    protected function _after()
    {
        $this->redisClient->flushall();
    }

    /**
     * @test
     */
    public function shouldGetReJSONModuleSuccessfully() : void
    {
        $this->assertInstanceOf(ReJSON::class, $this->reJsonModule, 'ReJSON module init.');
    }

    /**
     * @test
     * @expectedException \Redislabs\Exceptions\InvalidCommandException
     */
    public function shouldFailForInvalidReJSONCommand() : void
    {
        $this->reJsonModule->invalidCommand('-test-');
    }

    /**
     * @test
     * @expectedException \Redislabs\Module\ReJSON\Exceptions\InvalidExistentialModifierException
     */
    public function shouldFailReJSONCommandWhenInvalidExistentialModifierGiven() : void
    {
        $this->reJsonModule->set('test', '.', ['ttt' => ['deneme'=> 1]], 'NN');
    }

    /**
     * @test
     */
    public function shouldRunReJSONCommandSetAndGetAndTypeSuccessfully() : void
    {
        $result = $this->reJsonModule->set('test', '.', [], 'NX');
        $this->assertEquals('OK', $result, 'JSON.SET sets new value');
        $result = $this->reJsonModule->set('test', '.', ['foo'=>'bar'], 'XX');
        $this->assertEquals('OK', $result, 'JSON.SET sets .foo value');
        $result = $this->reJsonModule->set('test', '.baz', 'qux');
        $this->assertEquals('OK', $result, 'JSON.SET sets .baz:qux value');
        $result = $this->reJsonModule->set('test', '.baz', 'quux', 'XX');
        $this->assertEquals('OK', $result, 'JSON.SET sets existing .foo.baz value');
        $root = $this->reJsonModule->get('test');
        $this->assertEquals(
            'bar',
            $root->foo,
            'JSON.GET . and check for root element foo has correct value'
        );
        $baz = $this->reJsonModule->get('test', '.baz');
        $this->assertEquals(
            'quux',
            $baz,
            'JSON.GET .baz and check for baz element has correct value'
        );
        $this->reJsonModule->set('test', '.num', 1);
        $this->assertEquals('integer', $this->reJsonModule->type('test', '.num'));
    }

    /**
     * @test
     */
    public function shouldRunReJSONCommandDelAndForgetSuccessfully() : void
    {
        $result = $this->reJsonModule->set(
            'test',
            '.',
            ['foo'=>'bar', 'baz' => 'qux', 'quux' => ['quuz'=>'corge']],
            'NX'
        );
        $this->assertEquals('OK', $result, 'JSON.SET sets new value');
        $root = $this->reJsonModule->get('test', '.');
        $this->assertEquals('bar', $root->foo);
        $this->assertEquals(1, $this->reJsonModule->del('test', $path = '.foo'), 'Trying to delete .foo');
        $this->assertEquals(1, $this->reJsonModule->forget('test', $path = '.quux.quuz'), 'Trying to delete .baz.quux');
        $root = $this->reJsonModule->get('test', '.');
        $this->assertEquals('qux', $root->baz);
        $this->assertEquals(1, $this->reJsonModule->del('test', $path = '.'), 'Trying to delete root');
    }

    /**
     * @test
     * @expectedException \Redislabs\Exceptions\InvalidNumberOfArgumentsException
     */
    public function shouldFailForReJSONCommandMgetWhenNumberOfArgumentsAreInsufficient() : void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->mget('.foo');
    }

    /**
     * @test
     */
    public function shouldRunReJSONCommandMgetSuccessfully() : void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test1', '.', ['foo' => 'baz']);
        $this->reJsonModule->set('test2', '.', ['foo' => 'bar']);
        $this->reJsonModule->set('test3', '.', ['foo' => 'qux']);
        $this->reJsonModule->set('test4', '.', []);
        $mgetResult = $this->reJsonModule->mget('test1', 'test2', 'test3', 'test4', '.foo');
        $this->assertEquals(
            'baz',
            $mgetResult[0],
            'test1.foo = baz'
        );
        $this->assertEquals(
            'bar',
            $mgetResult[1],
            'test2.foo = bar'
        );
        $this->assertEquals(
            'qux',
            $mgetResult[2],
            'test3.foo = quuz'
        );
        $this->assertEquals(
            null,
            $mgetResult[3],
            'test4.foo is null'
        );
    }

    /**
     * @test
     */
    public function shouldRunReJSONNumCommandsSuccessfully() : void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test', '.', ['foo' => 1]);
        $value = $this->reJsonModule->get('test', 'foo');
        $incrementedValue = $this->reJsonModule->numincrby('test', 'foo', 4);
        $this->assertEquals($value+4, $incrementedValue);
        $multipliedValue = $this->reJsonModule->nummultby('test', 'foo', 2);
        $this->assertEquals($incrementedValue*2, $multipliedValue);
    }

    /**
     * @test
     */
    public function shouldRunReJSONStringCommandsSuccessfully() : void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test', '.', ['foo' => 'bar']);
        $appendedStringsLength = $this->reJsonModule->strappend('test', 'baz', '.foo');
        $this->assertEquals(6, $appendedStringsLength);
        $this->assertEquals('barbaz', $this->reJsonModule->get('test', '.foo'));
        $this->assertEquals(strlen('barbaz'), $this->reJsonModule->strlen('test', '.foo'));
    }

    /**
     * @test
     */
    public function shouldRunReJSONArrayCommandsSuccessfully() : void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test', '.', ['foo', 'bar']);
        $appendedArraysLength = $this->reJsonModule->arrappend('test', '.', 'baz', 'qux');
        $this->assertEquals(2+2, $appendedArraysLength);
        $this->assertEquals(1, $this->reJsonModule->arrindex('test', '.', 'bar'));
        $this->assertEquals(-1, $this->reJsonModule->arrindex('test', '.', 'quuz'));
        $arrayNewSize = $this->reJsonModule->arrinsert('test', '.', 2, 'quuz', 'quux');
        $this->assertEquals(6, $arrayNewSize);
        $this->assertEquals($arrayNewSize, $this->reJsonModule->arrlen('test', '.'));
        $this->assertEquals('qux', $this->reJsonModule->arrpop('test', '.', 5));
        $this->assertEquals($arrayNewSize-1, $this->reJsonModule->arrlen('test', '.'));
        $this->assertEquals(3, $this->reJsonModule->arrtrim('test', '.', 1, 3));
    }

    /**
     * @test
     */
    public function shouldRunReJSONObjectCommandsSuccessfully() : void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test', '.', ['foo' => 'bar', 'baz' => 'quz']);
        $this->assertEquals(['foo', 'baz'], $this->reJsonModule->objkeys('test', '.'));
        $this->assertEquals(2, $this->reJsonModule->objlen('test', '.'));
    }


    /**
     * @test
     */
    public function shouldRunReJSONDebugCommandsSuccessfully() : void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $help = $this->reJsonModule->debug('HELP');
        $this->assertContains('HELP', $help[1]);
        $this->reJsonModule->set('test', '.', ['foo', 'bar']);
        $memory = $this->reJsonModule->debug('MEMORY', 'test', '.');
        $this->assertEquals(94, $memory);
    }

    /**
     * @test
     * @expectedException \Redislabs\Module\ReJSON\Exceptions\InvalidDebugSubcommandException
     */
    public function shouldFailForInvalidDebugCommand() : void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->debug('LS');
    }

    /**
     * @test
     */
    public function shouldRunReJSONRespCommandSuccessfully() : void
    {
        /**
         * @var ReJSON $jsonModule
         */
        $this->reJsonModule->set('test', '.', ['foo' => 'bar', 'baz' => 'quz']);
        $resp = $this->reJsonModule->resp('test', '.');
        $this->assertEquals(['foo', 'bar'], $resp[1]);
        $this->assertEquals(['baz', 'quz'], $resp[2]);
        $this->assertCount(3, $resp);
    }
}
