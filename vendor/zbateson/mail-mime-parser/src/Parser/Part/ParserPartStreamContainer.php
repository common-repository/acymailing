<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use SplObserver;
use SplSubject;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;
use ZBateson\MailMimeParser\Stream\StreamFactory;

class ParserPartStreamContainer extends PartStreamContainer implements SplObserver
{
    protected $parserProxy;

    protected $parsedStream;

    protected $detachParsedStream = false;

    protected $partUpdated = false;

    protected $contentParseRequested = false;

    public function __construct(StreamFactory $streamFactory, ParserPartProxy $parserProxy)
    {
        parent::__construct($streamFactory);
        $this->parserProxy = $parserProxy;
    }

    public function __destruct()
    {
        if ($this->detachParsedStream && $this->parsedStream !== null) {
            $this->parsedStream->detach();
        }
    }

    protected function requestParsedContentStream() : self
    {
        if (!$this->contentParseRequested) {
            $this->contentParseRequested = true;
            $this->parserProxy->parseContent();
            parent::setContentStream($this->streamFactory->getLimitedContentStream(
                $this->parserProxy
            ));
        }
        return $this;
    }

    protected function requestParsedStream() : self
    {
        if ($this->parsedStream === null) {
            $this->parserProxy->parseAll();
            $this->parsedStream = $this->streamFactory->getLimitedPartStream(
                $this->parserProxy
            );
            if ($this->parsedStream !== null) {
                $this->detachParsedStream = ($this->parsedStream->getMetadata('mmp-detached-stream') === true);
            }
        }
        return $this;
    }

    public function hasContent() : bool
    {
        $this->requestParsedContentStream();
        return parent::hasContent();
    }

    public function getContentStream(?string $transferEncoding, ?string $fromCharset, ?string $toCharset)
    {
        $this->requestParsedContentStream();
        return parent::getContentStream($transferEncoding, $fromCharset, $toCharset);
    }

    public function getBinaryContentStream(?string $transferEncoding = null) : ?StreamInterface
    {
        $this->requestParsedContentStream();
        return parent::getBinaryContentStream($transferEncoding);
    }

    public function setContentStream(?StreamInterface $contentStream = null) : self
    {
        $this->requestParsedContentStream();
        parent::setContentStream($contentStream);
        return $this;
    }

    public function getStream()
    {
        $this->requestParsedStream();
        if (!$this->partUpdated) {
            if ($this->parsedStream !== null) {
                $this->parsedStream->rewind();
                return $this->parsedStream;
            }
        }
        return parent::getStream();
    }

    public function update(SplSubject $subject) : void
    {
        $this->partUpdated = true;
    }
}
