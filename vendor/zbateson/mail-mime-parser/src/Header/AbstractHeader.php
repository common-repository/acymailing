<?php

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;

abstract class AbstractHeader implements IHeader
{
    protected $name;

    protected $parts;

    protected $rawValue;

    public function __construct(ConsumerService $consumerService, string $name, string $value)
    {
        $this->name = $name;
        $this->rawValue = $value;

        $consumer = $this->getConsumer($consumerService);
        $this->setParseHeaderValue($consumer);
    }

    abstract protected function getConsumer(ConsumerService $consumerService);

    protected function setParseHeaderValue(AbstractConsumer $consumer)
    {
        $this->parts = $consumer($this->rawValue);
        return $this;
    }

    public function getParts() : array
    {
        return $this->parts;
    }

    public function getValue() : ?string
    {
        if (!empty($this->parts)) {
            return $this->parts[0]->getValue();
        }
        return null;
    }

    public function getRawValue() : string
    {
        return $this->rawValue;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function __toString() : string
    {
        return "{$this->name}: {$this->rawValue}";
    }
}
