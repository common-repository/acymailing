<?php

namespace ZBateson\MailMimeParser\Message;

use ArrayIterator;
use IteratorAggregate;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\IHeader;

class PartHeaderContainer implements IteratorAggregate
{
    protected $headerFactory;

    private $headers = [];

    private $headerObjects = [];

    private $headerMap = [];

    private $nextIndex = 0;

    public function __construct(HeaderFactory $headerFactory, ?PartHeaderContainer $cloneSource = null)
    {
        $this->headerFactory = $headerFactory;
        if ($cloneSource !== null) {
            $this->headers = $cloneSource->headers;
            $this->headerObjects = $cloneSource->headerObjects;
            $this->headerMap = $cloneSource->headerMap;
            $this->nextIndex = $cloneSource->nextIndex;
        }
    }

    public function exists($name, $offset = 0)
    {
        $s = $this->headerFactory->getNormalizedHeaderName($name);
        return isset($this->headerMap[$s][$offset]);
    }

    private function getAllWithOriginalHeaderNameIfSet(string $name) : ?array
    {
        $s = $this->headerFactory->getNormalizedHeaderName($name);
        if (isset($this->headerMap[$s])) {
            $self = $this;
            $filtered = \array_filter($this->headerMap[$s], function($h) use ($name, $self) {
                return (\strcasecmp($self->headers[$h][0], $name) === 0);
            });
            return (!empty($filtered)) ? $filtered : $this->headerMap[$s];
        }
        return null;
    }

    public function get(string $name, int $offset = 0)
    {
        $a = $this->getAllWithOriginalHeaderNameIfSet($name);
        if (!empty($a) && isset($a[$offset])) {
            return $this->getByIndex($a[$offset]);
        }
        return null;
    }

    public function getAs(string $name, string $iHeaderClass, int $offset = 0) : ?IHeader
    {
        $a = $this->getAllWithOriginalHeaderNameIfSet($name);
        if (!empty($a) && isset($a[$offset])) {
            return $this->getByIndexAs($a[$offset], $iHeaderClass);
        }
        return null;
    }

    public function getAll($name)
    {
        $a = $this->getAllWithOriginalHeaderNameIfSet($name);
        if (!empty($a)) {
            $self = $this;
            return \array_map(function($index) use ($self) {
                return $self->getByIndex($index);
            }, $a);
        }
        return [];
    }

    private function getByIndex(int $index)
    {
        if (!isset($this->headers[$index])) {
            return null;
        }
        if ($this->headerObjects[$index] === null) {
            $this->headerObjects[$index] = $this->headerFactory->newInstance(
                $this->headers[$index][0],
                $this->headers[$index][1]
            );
        }
        return $this->headerObjects[$index];
    }

    private function getByIndexAs(int $index, string $iHeaderClass) : ?IHeader
    {
        if (!isset($this->headers[$index])) {
            return null;
        }
        if ($this->headerObjects[$index] !== null && \get_class($this->headerObjects[$index]) === $iHeaderClass) {
            return $this->headerObjects[$index];
        }
        return $this->headerFactory->newInstanceOf(
            $this->headers[$index][0],
            $this->headers[$index][1],
            $iHeaderClass
        );
    }

    public function remove($name, $offset = 0)
    {
        $s = $this->headerFactory->getNormalizedHeaderName($name);
        if (isset($this->headerMap[$s][$offset])) {
            $index = $this->headerMap[$s][$offset];
            \array_splice($this->headerMap[$s], $offset, 1);
            unset($this->headers[$index], $this->headerObjects[$index]);

            return true;
        }
        return false;
    }

    public function removeAll($name)
    {
        $s = $this->headerFactory->getNormalizedHeaderName($name);
        if (!empty($this->headerMap[$s])) {
            foreach ($this->headerMap[$s] as $i) {
                unset($this->headers[$i], $this->headerObjects[$i]);

            }
            $this->headerMap[$s] = [];
            return true;
        }
        return false;
    }

    public function add($name, $value)
    {
        $s = $this->headerFactory->getNormalizedHeaderName($name);
        $this->headers[$this->nextIndex] = [$name, $value];
        $this->headerObjects[$this->nextIndex] = null;
        if (!isset($this->headerMap[$s])) {
            $this->headerMap[$s] = [];
        }
        $this->headerMap[$s][] = $this->nextIndex;
        $this->nextIndex++;
    }

    public function set($name, $value, $offset = 0) : self
    {
        $s = $this->headerFactory->getNormalizedHeaderName($name);
        if (!isset($this->headerMap[$s][$offset])) {
            $this->add($name, $value);
            return $this;
        }
        $i = $this->headerMap[$s][$offset];
        $this->headers[$i] = [$name, $value];
        $this->headerObjects[$i] = null;
        return $this;
    }

    public function getHeaderObjects()
    {
        return \array_filter(\array_map([$this, 'getByIndex'], \array_keys($this->headers)));
    }

    public function getHeaders()
    {
        return \array_values(\array_filter($this->headers));
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->getHeaders());
    }
}
