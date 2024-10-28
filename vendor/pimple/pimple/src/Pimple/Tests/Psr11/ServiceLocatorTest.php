<?php


namespace Pimple\Tests\Psr11;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Pimple\Psr11\ServiceLocator;
use Pimple\Tests\Fixtures;

class ServiceLocatorTest extends TestCase
{
    public function testCanAccessServices()
    {
        $pimple = new Container();
        $pimple['service'] = function () {
            return new Fixtures\Service();
        };
        $locator = new ServiceLocator($pimple, ['service']);

        $this->assertSame($pimple['service'], $locator->get('service'));
    }

    public function testCanAccessAliasedServices()
    {
        $pimple = new Container();
        $pimple['service'] = function () {
            return new Fixtures\Service();
        };
        $locator = new ServiceLocator($pimple, ['alias' => 'service']);

        $this->assertSame($pimple['service'], $locator->get('alias'));
    }

    public function testCannotAccessAliasedServiceUsingRealIdentifier()
    {
        $this->expectException(\Pimple\Exception\UnknownIdentifierException::class);
        $this->expectExceptionMessage('Identifier "service" is not defined.');

        $pimple = new Container();
        $pimple['service'] = function () {
            return new Fixtures\Service();
        };
        $locator = new ServiceLocator($pimple, ['alias' => 'service']);

        $service = $locator->get('service');
    }

    public function testGetValidatesServiceCanBeLocated()
    {
        $this->expectException(\Pimple\Exception\UnknownIdentifierException::class);
        $this->expectExceptionMessage('Identifier "foo" is not defined.');

        $pimple = new Container();
        $pimple['service'] = function () {
            return new Fixtures\Service();
        };
        $locator = new ServiceLocator($pimple, ['alias' => 'service']);

        $service = $locator->get('foo');
    }

    public function testGetValidatesTargetServiceExists()
    {
        $this->expectException(\Pimple\Exception\UnknownIdentifierException::class);
        $this->expectExceptionMessage('Identifier "invalid" is not defined.');

        $pimple = new Container();
        $pimple['service'] = function () {
            return new Fixtures\Service();
        };
        $locator = new ServiceLocator($pimple, ['alias' => 'invalid']);

        $service = $locator->get('alias');
    }

    public function testHasValidatesServiceCanBeLocated()
    {
        $pimple = new Container();
        $pimple['service1'] = function () {
            return new Fixtures\Service();
        };
        $pimple['service2'] = function () {
            return new Fixtures\Service();
        };
        $locator = new ServiceLocator($pimple, ['service1']);

        $this->assertTrue($locator->has('service1'));
        $this->assertFalse($locator->has('service2'));
    }

    public function testHasChecksIfTargetServiceExists()
    {
        $pimple = new Container();
        $pimple['service'] = function () {
            return new Fixtures\Service();
        };
        $locator = new ServiceLocator($pimple, ['foo' => 'service', 'bar' => 'invalid']);

        $this->assertTrue($locator->has('foo'));
        $this->assertFalse($locator->has('bar'));
    }
}
