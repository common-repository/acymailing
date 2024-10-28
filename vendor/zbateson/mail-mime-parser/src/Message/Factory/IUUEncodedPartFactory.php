<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use ZBateson\MailMimeParser\Message\UUEncodedPart;

class IUUEncodedPartFactory extends IMessagePartFactory
{
    public function newInstance()
    {
        $streamContainer = $this->partStreamContainerFactory->newInstance();
        $part = new UUEncodedPart(
            null,
            null,
            null,
            $streamContainer
        );
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        return $part;
    }
}
