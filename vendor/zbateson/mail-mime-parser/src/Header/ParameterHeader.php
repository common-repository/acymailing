<?php

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\ParameterPart;

class ParameterHeader extends AbstractHeader
{
    protected $parameters = [];

    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getParameterConsumer();
    }

    protected function setParseHeaderValue(AbstractConsumer $consumer)
    {
        parent::setParseHeaderValue($consumer);
        foreach ($this->parts as $part) {
            if ($part instanceof ParameterPart) {
                $this->parameters[\strtolower($part->getName())] = $part;
            }
        }
        return $this;
    }

    public function hasParameter(string $name) : bool
    {
        return isset($this->parameters[\strtolower($name)]);
    }

    public function getValueFor(string $name, ?string $defaultValue = null) : ?string
    {
        if (!$this->hasParameter($name)) {
            return $defaultValue;
        }
        return $this->parameters[\strtolower($name)]->getValue();
    }
}
