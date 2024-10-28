<?php

namespace ZBateson\MailMimeParser\Message;

use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use SplObjectStorage;
use SplObserver;
use ZBateson\MailMimeParser\MailMimeParser;

abstract class MessagePart implements IMessagePart
{
    protected $parent;

    protected $partStreamContainer;

    protected $charsetOverride;

    protected $ignoreTransferEncoding;

    protected $observers;

    public function __construct(PartStreamContainer $streamContainer, ?IMimePart $parent = null)
    {
        $this->partStreamContainer = $streamContainer;
        $this->parent = $parent;
        $this->observers = new SplObjectStorage();
    }

    public function attach(SplObserver $observer) : void
    {
        $this->observers->attach($observer);
    }

    public function detach(SplObserver $observer) : void
    {
        $this->observers->detach($observer);
    }

    public function notify() : void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
        if ($this->parent !== null) {
            $this->parent->notify();
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function hasContent() : bool
    {
        return $this->partStreamContainer->hasContent();
    }

    public function getFilename() : ?string
    {
        return null;
    }

    public function setCharsetOverride(string $charsetOverride, bool $onlyIfNoCharset = false)
    {
        if (!$onlyIfNoCharset || $this->getCharset() === null) {
            $this->charsetOverride = $charsetOverride;
        }
        return $this;
    }

    public function getContentStream(string $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        if ($this->hasContent()) {
            $tr = ($this->ignoreTransferEncoding) ? '' : $this->getContentTransferEncoding();
            $ch = $this->charsetOverride ?? $this->getCharset();
            return $this->partStreamContainer->getContentStream(
                $tr,
                $ch,
                $charset
            );
        }
        return null;
    }

    public function getBinaryContentStream()
    {
        if ($this->hasContent()) {
            $tr = ($this->ignoreTransferEncoding) ? '' : $this->getContentTransferEncoding();
            return $this->partStreamContainer->getBinaryContentStream($tr);
        }
        return null;
    }

    public function getBinaryContentResourceHandle()
    {
        $stream = $this->getBinaryContentStream();
        if ($stream !== null) {
            return StreamWrapper::getResource($stream);
        }
        return null;
    }

    public function saveContent($filenameResourceOrStream) : self
    {
        $resourceOrStream = $filenameResourceOrStream;
        if (\is_string($filenameResourceOrStream)) {
            $resourceOrStream = \fopen($filenameResourceOrStream, 'w+');
        }

        $stream = Utils::streamFor($resourceOrStream);
        Utils::copyToStream($this->getBinaryContentStream(), $stream);

        if (!\is_string($filenameResourceOrStream)
            && !($filenameResourceOrStream instanceof StreamInterface)) {
            $stream->detach();
        }
        return $this;
    }

    public function getContent(string $charset = MailMimeParser::DEFAULT_CHARSET) : ?string
    {
        $stream = $this->getContentStream($charset);
        if ($stream !== null) {
            return $stream->getContents();
        }
        return null;
    }

    public function attachContentStream(StreamInterface $stream, string $streamCharset = MailMimeParser::DEFAULT_CHARSET)
    {
        $ch = $this->charsetOverride ?? $this->getCharset();
        if ($ch !== null && $streamCharset !== $ch) {
            $this->charsetOverride = $streamCharset;
        }
        $this->ignoreTransferEncoding = true;
        $this->partStreamContainer->setContentStream($stream);
        $this->notify();
        return $this;
    }

    public function detachContentStream()
    {
        $this->partStreamContainer->setContentStream(null);
        $this->notify();
        return $this;
    }

    public function setContent($resource, string $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $stream = Utils::streamFor($resource);
        $this->attachContentStream($stream, $charset);
        return $this;
    }

    public function getResourceHandle()
    {
        return StreamWrapper::getResource($this->getStream());
    }

    public function getStream()
    {
        return $this->partStreamContainer->getStream();
    }

    public function save($filenameResourceOrStream, string $filemode = 'w+')
    {
        $resourceOrStream = $filenameResourceOrStream;
        if (\is_string($filenameResourceOrStream)) {
            $resourceOrStream = \fopen($filenameResourceOrStream, $filemode);
        }

        $partStream = $this->getStream();
        $partStream->rewind();
        $stream = Utils::streamFor($resourceOrStream);
        Utils::copyToStream($partStream, $stream);

        if (!\is_string($filenameResourceOrStream)
            && !($filenameResourceOrStream instanceof StreamInterface)) {
            $stream->detach();
        }
        return $this;
    }

    public function __toString() : string
    {
        return $this->getStream()->getContents();
    }
}
