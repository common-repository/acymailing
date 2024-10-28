<?php

namespace ZBateson\MailMimeParser\Message;

use ArrayAccess;
use InvalidArgumentException;
use RecursiveIterator;

class PartChildrenContainer implements ArrayAccess, RecursiveIterator
{
    protected $children;

    protected $position = 0;

    public function __construct(array $children = [])
    {
        $this->children = $children;
    }

    public function hasChildren() : bool
    {
        return ($this->current() instanceof IMultiPart
            && $this->current()->getChildIterator() !== null);
    }

    public function getChildren() : ?RecursiveIterator
    {
        if ($this->current() instanceof IMultiPart) {
            return $this->current()->getChildIterator();
        }
        return null;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->offsetGet($this->position);
    }

    public function key() : int
    {
        return $this->position;
    }

    public function next() : void
    {
        ++$this->position;
    }

    public function rewind() : void
    {
        $this->position = 0;
    }

    public function valid() : bool
    {
        return $this->offsetExists($this->position);
    }

    public function add(IMessagePart $part, $position = null)
    {
        $index = $position ?? \count($this->children);
        \array_splice(
            $this->children,
            $index,
            0,
            [$part]
        );
    }

    public function remove(IMessagePart $part) : ?int
    {
        foreach ($this->children as $key => $child) {
            if ($child === $part) {
                $this->offsetUnset($key);
                return $key;
            }
        }
        return null;
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->children[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->children[$offset] : null;
    }

    public function offsetSet($offset, $value) : void
    {
        if (!$value instanceof IMessagePart) {
            throw new InvalidArgumentException(
                \get_class($value) . ' is not a ZBateson\MailMimeParser\Message\IMessagePart'
            );
        }
        $index = $offset ?? \count($this->children);
        $this->children[$index] = $value;
        if ($index < $this->position) {
            ++$this->position;
        }
    }

    public function offsetUnset($offset) : void
    {
        \array_splice($this->children, $offset, 1);
        if ($this->position >= $offset) {
            --$this->position;
        }
    }
}
