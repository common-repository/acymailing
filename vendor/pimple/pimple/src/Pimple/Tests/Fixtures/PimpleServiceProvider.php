<?php


namespace Pimple\Tests\Fixtures;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class PimpleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['param'] = 'value';

        $pimple['service'] = function () {
            return new Service();
        };

        $pimple['factory'] = $pimple->factory(function () {
            return new Service();
        });
    }
}
