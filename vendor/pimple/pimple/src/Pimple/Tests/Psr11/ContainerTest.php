<?php


namespace Pimple\Tests\Psr11;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Pimple\Psr11\Container as PsrContainer;
use Pimple\Tests\Fixtures\Service;

class ContainerTest extends TestCase
{
    public function testGetReturnsExistingService()
    {
        $pimple = new Container();
        $pimple['service'] = function () {
            return new Service();
        };
        $psr = new PsrContainer($pimple);

        $this->assertSame($pimple['service'], $psr->get('service'));
    }

    public function testGetThrowsExceptionIfServiceIsNotFound()
    {
        $this->expectException(\Psr\Container\NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Identifier "service" is not defined.');

        $pimple = new Container();
        $psr = new PsrContainer($pimple);

        $psr->get('service');
    }

    public function testHasReturnsTrueIfServiceExists()
    {
        $pimple = new Container();
        $pimple['service'] = function () {
            return new Service();
        };
        $psr = new PsrContainer($pimple);

        $this->assertTrue($psr->has('service'));
    }

    public function testHasReturnsFalseIfServiceDoesNotExist()
    {
        $pimple = new Container();
        $psr = new PsrContainer($pimple);

        $this->assertFalse($psr->has('service'));
    }
}
