<?php

namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMessageProxyFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxyFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;

class MimeParser extends AbstractParser
{
    protected $partHeaderContainerFactory;

    protected $headerParser;

    public function __construct(
        ParserMessageProxyFactory $parserMessageProxyFactory,
        ParserMimePartProxyFactory $parserMimePartProxyFactory,
        PartBuilderFactory $partBuilderFactory,
        PartHeaderContainerFactory $partHeaderContainerFactory,
        HeaderParser $headerParser
    ) {
        parent::__construct($parserMessageProxyFactory, $parserMimePartProxyFactory, $partBuilderFactory);
        $this->partHeaderContainerFactory = $partHeaderContainerFactory;
        $this->headerParser = $headerParser;
    }

    public function canParse(PartBuilder $part) : bool
    {
        return $part->isMime();
    }

    private function readBoundaryLine($handle, ParserMimePartProxy $proxy) : string
    {
        $size = 2048;
        $isCut = false;
        $line = \fgets($handle, $size);
        while (\strlen($line) === $size - 1 && \substr($line, -1) !== "\n") {
            $line = \fgets($handle, $size);
            $isCut = true;
        }
        $ret = \rtrim($line, "\r\n");
        $proxy->setLastLineEndingLength(\strlen($line) - \strlen($ret));
        return ($isCut) ? '' : $ret;
    }

    private function findContentBoundary(ParserMimePartProxy $proxy) : self
    {
        $handle = $proxy->getMessageResourceHandle();
        while (!\feof($handle)) {
            $endPos = \ftell($handle) - $proxy->getLastLineEndingLength();
            $line = $this->readBoundaryLine($handle, $proxy);
            if (\substr($line, 0, 2) === '--' && $proxy->setEndBoundaryFound($line)) {
                $proxy->setStreamPartAndContentEndPos($endPos);
                return $this;
            }
        }
        $proxy->setStreamPartAndContentEndPos(\ftell($handle));
        $proxy->setEof();
        return $this;
    }

    public function parseContent(ParserPartProxy $proxy)
    {
        $proxy->setStreamContentStartPos($proxy->getMessageResourceHandlePos());
        $this->findContentBoundary($proxy);
        return $this;
    }

    private function createPart(ParserMimePartProxy $parent, PartHeaderContainer $headerContainer, PartBuilder $child)
    {
        if (!$parent->isEndBoundaryFound()) {
            $this->headerParser->parse(
                $child->getMessageResourceHandle(),
                $headerContainer
            );
            $parserProxy = $this->parserManager->createParserProxyFor($child);
            return $parserProxy;
        }
        $parserProxy = $this->parserPartProxyFactory->newInstance($child, $this);
        $this->parseContent($parserProxy);
        return null;
    }

    public function parseNextChild(ParserMimePartProxy $proxy)
    {
        if ($proxy->isParentBoundaryFound()) {
            return null;
        }
        $headerContainer = $this->partHeaderContainerFactory->newInstance();
        $child = $this->partBuilderFactory->newChildPartBuilder($headerContainer, $proxy);
        return $this->createPart($proxy, $headerContainer, $child);
    }
}
