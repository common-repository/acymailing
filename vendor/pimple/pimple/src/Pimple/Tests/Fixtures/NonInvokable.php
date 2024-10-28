<?php


namespace Pimple\Tests\Fixtures;

class NonInvokable
{
    public function __call($a, $b)
    {
    }
}
