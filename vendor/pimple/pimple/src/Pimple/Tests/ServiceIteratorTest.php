<?php


namespace Pimple\Tests;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Pimple\ServiceIterator;
use Pimple\Tests\Fixtures\Service;

class ServiceIteratorTest extends TestCase
{
    public function testIsIterable()
    {
        $pimple = new Container();
        $pimple['service1'] = function () {
            return new Service();
        };
        $pimple['service2'] = function () {
            return new Service();
        };
        $pimple['service3'] = function () {
            return new Service();
        };
        $iterator = new ServiceIterator($pimple, ['service1', 'service2']);

        $this->assertSame(['service1' => $pimple['service1'], 'service2' => $pimple['service2']], iterator_to_array($iterator));
    }
}
