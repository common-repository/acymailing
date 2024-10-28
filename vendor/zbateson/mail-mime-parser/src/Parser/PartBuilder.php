<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7\StreamWrapper;
use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;

class PartBuilder
{
    private $streamPartStartPos = null;

    private $streamPartEndPos = null;

    private $streamContentStartPos = null;

    private $streamContentEndPos = null;

    private $headerContainer;

    private $messageStream = null;

    private $messageHandle = null;

    private $parent = null;

    public function __construct(PartHeaderContainer $headerContainer, ?StreamInterface $messageStream = null, ?ParserPartProxy $parent = null)
    {
        $this->headerContainer = $headerContainer;
        $this->messageStream = $messageStream;
        $this->parent = $parent;
        if ($messageStream !== null) {
            $this->messageHandle = StreamWrapper::getResource($messageStream);
        }
        $this->setStreamPartStartPos($this->getMessageResourceHandlePos());
    }

    public function __destruct()
    {
        if ($this->messageHandle) {
            \fclose($this->messageHandle);
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getHeaderContainer()
    {
        return $this->headerContainer;
    }

    public function getStream()
    {
        return ($this->parent !== null) ?
            $this->parent->getStream() :
            $this->messageStream;
    }

    public function getMessageResourceHandle()
    {
        return ($this->parent !== null) ?
            $this->parent->getMessageResourceHandle() :
            $this->messageHandle;
    }

    public function getMessageResourceHandlePos() : int
    {
        return \ftell($this->getMessageResourceHandle());
    }

    public function getStreamPartStartPos() : ?int
    {
        return $this->streamPartStartPos;
    }

    public function getStreamPartLength() : int
    {
        return $this->streamPartEndPos - $this->streamPartStartPos;
    }

    public function getStreamContentStartPos() : ?int
    {
        return $this->streamContentStartPos;
    }

    public function getStreamContentLength() : int
    {
        return $this->streamContentEndPos - $this->streamContentStartPos;
    }

    public function setStreamPartStartPos(int $streamPartStartPos)
    {
        $this->streamPartStartPos = $streamPartStartPos;
        return $this;
    }

    public function setStreamPartEndPos(int $streamPartEndPos)
    {
        $this->streamPartEndPos = $streamPartEndPos;
        if ($this->parent !== null) {
            $this->parent->setStreamPartEndPos($streamPartEndPos);
        }
        return $this;
    }

    public function setStreamContentStartPos(int $streamContentStartPos)
    {
        $this->streamContentStartPos = $streamContentStartPos;
        return $this;
    }

    public function setStreamPartAndContentEndPos(int $streamContentEndPos)
    {
        $this->streamContentEndPos = $streamContentEndPos;
        $this->setStreamPartEndPos($streamContentEndPos);
        return $this;
    }

    public function isContentParsed() : ?bool
    {
        return ($this->streamContentEndPos !== null);
    }

    public function isMime() : bool
    {
        if ($this->getParent() !== null) {
            return $this->getParent()->isMime();
        }
        return ($this->headerContainer->exists(HeaderConsts::CONTENT_TYPE) ||
            $this->headerContainer->exists(HeaderConsts::MIME_VERSION));
    }
}
