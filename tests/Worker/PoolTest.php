<?php
namespace Icicle\Tests\Concurrent\Worker;

use Icicle\Concurrent\Worker\Pool;
use Icicle\Coroutine;
use Icicle\Loop;
use Icicle\Tests\Concurrent\TestCase;

class PoolTest extends TestCase
{
    public function createPool($min = null, $max = null)
    {
        return new Pool($min, $max);
    }

    public function testIsRunning()
    {
        Coroutine\run(function () {
            $pool = $this->createPool();
            $this->assertFalse($pool->isRunning());

            $pool->start();
            $this->assertTrue($pool->isRunning());

            yield $pool->shutdown();
            $this->assertFalse($pool->isRunning());
        });
    }

    public function testIsIdleOnStart()
    {
        Coroutine\run(function () {
            $pool = $this->createPool();
            $pool->start();

            $this->assertTrue($pool->isIdle());

            yield $pool->shutdown();
        });
    }

    public function testGetMinSize()
    {
        $pool = $this->createPool(7, 24);
        $this->assertEquals(7, $pool->getMinSize());
    }

    public function testGetMaxSize()
    {
        $pool = $this->createPool(3, 17);
        $this->assertEquals(17, $pool->getMaxSize());
    }

    public function testMinWorkersSpawnedOnStart()
    {
        Coroutine\run(function () {
            $pool = $this->createPool(8, 32);
            $pool->start();

            $this->assertEquals(8, $pool->getWorkerCount());

            yield $pool->shutdown();
        });
    }

    public function testWorkersIdleOnStart()
    {
        Coroutine\run(function () {
            $pool = $this->createPool(8, 32);
            $pool->start();

            $this->assertEquals(8, $pool->getIdleWorkerCount());

            yield $pool->shutdown();
        });
    }

    public function testEnqueue()
    {
        Coroutine\run(function () {
            $pool = $this->createPool();
            $pool->start();

            $returnValue = (yield $pool->enqueue(new TestTask(42)));
            $this->assertEquals(42, $returnValue);

            yield $pool->shutdown();
        });
    }

    public function testKill()
    {
        $pool = $this->createPool();
        $pool->start();

        $this->assertRunTimeLessThan([$pool, 'kill'], 0.5);
        $this->assertFalse($pool->isRunning());
    }
}
