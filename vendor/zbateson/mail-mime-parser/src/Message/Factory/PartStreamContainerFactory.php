<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Stream\StreamFactory;

class PartStreamContainerFactory
{
    protected $streamFactory;

    public function __construct(StreamFactory $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    public function newInstance()
    {
        return new PartStreamContainer($this->streamFactory);
    }
}
