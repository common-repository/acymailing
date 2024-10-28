<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use ZBateson\MailMimeParser\Stream\StreamFactory;

abstract class IMessagePartFactory
{
    protected $streamFactory;

    protected $partStreamContainerFactory;

    public function __construct(
        StreamFactory $streamFactory,
        PartStreamContainerFactory $partStreamContainerFactory
    ) {
        $this->streamFactory = $streamFactory;
        $this->partStreamContainerFactory = $partStreamContainerFactory;
    }

    abstract public function newInstance();
}
