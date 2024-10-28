<?php


namespace Pimple\Tests;

use PHPUnit\Framework\TestCase;
use Pimple\Container;

class PimpleServiceProviderInterfaceTest extends TestCase
{
    public function testProvider()
    {
        $pimple = new Container();

        $pimpleServiceProvider = new Fixtures\PimpleServiceProvider();
        $pimpleServiceProvider->register($pimple);

        $this->assertEquals('value', $pimple['param']);
        $this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $pimple['service']);

        $serviceOne = $pimple['factory'];
        $this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceOne);

        $serviceTwo = $pimple['factory'];
        $this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceTwo);

        $this->assertNotSame($serviceOne, $serviceTwo);
    }

    public function testProviderWithRegisterMethod()
    {
        $pimple = new Container();

        $pimple->register(new Fixtures\PimpleServiceProvider(), [
            'anotherParameter' => 'anotherValue',
        ]);

        $this->assertEquals('value', $pimple['param']);
        $this->assertEquals('anotherValue', $pimple['anotherParameter']);

        $this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $pimple['service']);

        $serviceOne = $pimple['factory'];
        $this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceOne);

        $serviceTwo = $pimple['factory'];
        $this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceTwo);

        $this->assertNotSame($serviceOne, $serviceTwo);
    }
}
