<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Parser\PartBuilder;

abstract class ParserPartProxy extends PartBuilder
{
    protected $parser;

    protected $partBuilder;

    private $part;

    public function __construct(PartBuilder $partBuilder, IParser $parser)
    {
        $this->partBuilder = $partBuilder;
        $this->parser = $parser;
    }

    public function setPart(IMessagePart $part) : self
    {
        $this->part = $part;
        return $this;
    }

    public function getPart()
    {
        return $this->part;
    }

    public function parseContent()
    {
        if (!$this->isContentParsed()) {
            $this->parser->parseContent($this);
        }
        return $this;
    }

    public function parseAll()
    {
        $this->parseContent();
        return $this;
    }

    public function getParent()
    {
        return $this->partBuilder->getParent();
    }

    public function getHeaderContainer()
    {
        return $this->partBuilder->getHeaderContainer();
    }

    public function getStream()
    {
        return $this->partBuilder->getStream();
    }

    public function getMessageResourceHandle()
    {
        return $this->partBuilder->getMessageResourceHandle();
    }

    public function getMessageResourceHandlePos() : int
    {
        return $this->partBuilder->getMessageResourceHandlePos();
    }

    public function getStreamPartStartPos() : int
    {
        return $this->partBuilder->getStreamPartStartPos();
    }

    public function getStreamPartLength() : int
    {
        return $this->partBuilder->getStreamPartLength();
    }

    public function getStreamContentStartPos() : ?int
    {
        return $this->partBuilder->getStreamContentStartPos();
    }

    public function getStreamContentLength() : int
    {
        return $this->partBuilder->getStreamContentLength();
    }

    public function setStreamPartStartPos(int $streamPartStartPos)
    {
        $this->partBuilder->setStreamPartStartPos($streamPartStartPos);
        return $this;
    }

    public function setStreamPartEndPos(int $streamPartEndPos)
    {
        $this->partBuilder->setStreamPartEndPos($streamPartEndPos);
        return $this;
    }

    public function setStreamContentStartPos(int $streamContentStartPos)
    {
        $this->partBuilder->setStreamContentStartPos($streamContentStartPos);
        return $this;
    }

    public function setStreamPartAndContentEndPos(int $streamContentEndPos)
    {
        $this->partBuilder->setStreamPartAndContentEndPos($streamContentEndPos);
        return $this;
    }

    public function isContentParsed() : ?bool
    {
        return $this->partBuilder->isContentParsed();
    }

    public function isMime() : bool
    {
        return $this->partBuilder->isMime();
    }
}
