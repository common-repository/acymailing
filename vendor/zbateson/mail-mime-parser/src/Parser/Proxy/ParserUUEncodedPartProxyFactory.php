<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Message\UUEncodedPart;
use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Stream\StreamFactory;

class ParserUUEncodedPartProxyFactory extends ParserPartProxyFactory
{
    protected $streamFactory;

    protected $parserPartStreamContainerFactory;

    public function __construct(StreamFactory $sdf, ParserPartStreamContainerFactory $parserPartStreamContainerFactory)
    {
        $this->streamFactory = $sdf;
        $this->parserPartStreamContainerFactory = $parserPartStreamContainerFactory;
    }

    public function newInstance(PartBuilder $partBuilder, IParser $parser)
    {
        $parserProxy = new ParserUUEncodedPartProxy($partBuilder, $parser);
        $streamContainer = $this->parserPartStreamContainerFactory->newInstance($parserProxy);

        $part = new UUEncodedPart(
            $parserProxy->getUnixFileMode(),
            $parserProxy->getFileName(),
            $partBuilder->getParent()->getPart(),
            $streamContainer
        );
        $parserProxy->setPart($part);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);
        return $parserProxy;
    }
}
