<?php


namespace Pimple\Psr11;

use Pimple\Container as PimpleContainer;
use Pimple\Exception\UnknownIdentifierException;
use Psr\Container\ContainerInterface;

class ServiceLocator implements ContainerInterface
{
    private $container;
    private $aliases = [];

    public function __construct(PimpleContainer $container, array $ids)
    {
        $this->container = $container;

        foreach ($ids as $key => $id) {
            $this->aliases[\is_int($key) ? $id : $key] = $id;
        }
    }

    public function get(string $id)
    {
        if (!isset($this->aliases[$id])) {
            throw new UnknownIdentifierException($id);
        }

        return $this->container[$this->aliases[$id]];
    }

    public function has(string $id): bool
    {
        return isset($this->aliases[$id]) && isset($this->container[$this->aliases[$id]]);
    }
}
