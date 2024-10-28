<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Stream\StreamFactory;

class IMimePartFactory extends IMessagePartFactory
{
    protected $partHeaderContainerFactory;

    protected $partChildrenContainerFactory;

    public function __construct(
        StreamFactory $streamFactory,
        PartStreamContainerFactory $partStreamContainerFactory,
        PartHeaderContainerFactory $partHeaderContainerFactory,
        PartChildrenContainerFactory $partChildrenContainerFactory
    ) {
        parent::__construct($streamFactory, $partStreamContainerFactory);
        $this->partHeaderContainerFactory = $partHeaderContainerFactory;
        $this->partChildrenContainerFactory = $partChildrenContainerFactory;
    }

    public function newInstance()
    {
        $streamContainer = $this->partStreamContainerFactory->newInstance();
        $headerContainer = $this->partHeaderContainerFactory->newInstance();
        $part = new MimePart(
            null,
            $streamContainer,
            $headerContainer,
            $this->partChildrenContainerFactory->newInstance()
        );
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        return $part;
    }
}
