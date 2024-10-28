<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainerFactory;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Stream\StreamFactory;

class ParserMimePartProxyFactory extends ParserPartProxyFactory
{
    protected $streamFactory;

    protected $parserPartStreamContainerFactory;

    protected $partHeaderContainerFactory;

    protected $parserPartChildrenContainerFactory;

    public function __construct(
        StreamFactory $sdf,
        PartHeaderContainerFactory $phcf,
        ParserPartStreamContainerFactory $pscf,
        ParserPartChildrenContainerFactory $ppccf
    ) {
        $this->streamFactory = $sdf;
        $this->partHeaderContainerFactory = $phcf;
        $this->parserPartStreamContainerFactory = $pscf;
        $this->parserPartChildrenContainerFactory = $ppccf;
    }

    public function newInstance(PartBuilder $partBuilder, IParser $parser)
    {
        $parserProxy = new ParserMimePartProxy($partBuilder, $parser);

        $streamContainer = $this->parserPartStreamContainerFactory->newInstance($parserProxy);
        $headerContainer = $this->partHeaderContainerFactory->newInstance($parserProxy->getHeaderContainer());
        $childrenContainer = $this->parserPartChildrenContainerFactory->newInstance($parserProxy);

        $part = new MimePart(
            $partBuilder->getParent()->getPart(),
            $streamContainer,
            $headerContainer,
            $childrenContainer
        );
        $parserProxy->setPart($part);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);
        return $parserProxy;
    }
}
