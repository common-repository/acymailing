<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;
use ZBateson\MailMimeParser\Stream\StreamFactory;

class ParserPartStreamContainerFactory
{
    protected $streamFactory;

    public function __construct(StreamFactory $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    public function newInstance(ParserPartProxy $parserProxy)
    {
        return new ParserPartStreamContainer($this->streamFactory, $parserProxy);
    }
}
