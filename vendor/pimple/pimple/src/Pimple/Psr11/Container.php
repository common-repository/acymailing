<?php


namespace Pimple\Psr11;

use Pimple\Container as PimpleContainer;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{
    private $pimple;

    public function __construct(PimpleContainer $pimple)
    {
        $this->pimple = $pimple;
    }

    public function get(string $id)
    {
        return $this->pimple[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->pimple[$id]);
    }
}
