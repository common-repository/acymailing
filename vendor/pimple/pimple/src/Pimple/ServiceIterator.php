<?php


namespace Pimple;

final class ServiceIterator implements \Iterator
{
    private $container;
    private $ids;

    public function __construct(Container $container, array $ids)
    {
        $this->container = $container;
        $this->ids = $ids;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        \reset($this->ids);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->container[\current($this->ids)];
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return \current($this->ids);
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        \next($this->ids);
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return null !== \key($this->ids);
    }
}
