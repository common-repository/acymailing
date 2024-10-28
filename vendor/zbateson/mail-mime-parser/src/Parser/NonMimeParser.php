<?php

namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainerFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxyFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserUUEncodedPartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserUUEncodedPartProxyFactory;

class NonMimeParser extends AbstractParser
{
    protected $partHeaderContainerFactory;

    public function __construct(
        ParserNonMimeMessageProxyFactory $parserNonMimeMessageProxyFactory,
        ParserUUEncodedPartProxyFactory $parserUuEncodedPartProxyFactory,
        PartBuilderFactory $partBuilderFactory,
        UUEncodedPartHeaderContainerFactory $uuEncodedPartHeaderContainerFactory
    ) {
        parent::__construct($parserNonMimeMessageProxyFactory, $parserUuEncodedPartProxyFactory, $partBuilderFactory);
        $this->partHeaderContainerFactory = $uuEncodedPartHeaderContainerFactory;
    }

    public function canParse(PartBuilder $part) : bool
    {
        return true;
    }

    private function createPart(ParserNonMimeMessageProxy $parent)
    {
        $hc = $this->partHeaderContainerFactory->newInstance($parent->getNextPartMode(), $parent->getNextPartFilename());
        $pb = $this->partBuilderFactory->newChildPartBuilder($hc, $parent);
        $proxy = $this->parserManager->createParserProxyFor($pb);
        $pb->setStreamPartStartPos($parent->getNextPartStart());
        $pb->setStreamContentStartPos($parent->getNextPartStart());
        return $proxy;
    }

    private function parseNextPart(ParserPartProxy $proxy) : self
    {
        $handle = $proxy->getMessageResourceHandle();
        while (!\feof($handle)) {
            $start = \ftell($handle);
            $line = \trim(MessageParser::readLine($handle));
            if (\preg_match('/^begin ([0-7]{3}) (.*)$/', $line, $matches)) {
                $proxy->setNextPartStart($start);
                $proxy->setNextPartMode((int) $matches[1]);
                $proxy->setNextPartFilename($matches[2]);
                return $this;
            }
            $proxy->setStreamPartAndContentEndPos(\ftell($handle));
        }
        return $this;
    }

    public function parseContent(ParserPartProxy $proxy)
    {
        $handle = $proxy->getMessageResourceHandle();
        if ($proxy->getNextPartStart() !== null || \feof($handle)) {
            return $this;
        }
        if ($proxy->getStreamContentStartPos() === null) {
            $proxy->setStreamContentStartPos(\ftell($handle));
        }
        $this->parseNextPart($proxy);
        return $this;
    }

    public function parseNextChild(ParserMimePartProxy $proxy)
    {
        $handle = $proxy->getMessageResourceHandle();
        if ($proxy->getNextPartStart() === null || \feof($handle)) {
            return null;
        }
        $child = $this->createPart($proxy);
        $proxy->clearNextPart();
        return $child;
    }
}
