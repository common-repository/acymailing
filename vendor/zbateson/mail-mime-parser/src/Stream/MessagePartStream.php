<?php

namespace ZBateson\MailMimeParser\Stream;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use SplObserver;
use SplSubject;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\IMimePart;

#[\AllowDynamicProperties]
class MessagePartStream implements SplObserver, StreamInterface
{
    use StreamDecoratorTrait;

    protected $streamFactory;

    protected $part;

    protected $appendStream = null;

    public function __construct(StreamFactory $sdf, IMessagePart $part)
    {
        $this->streamFactory = $sdf;
        $this->part = $part;
        $part->attach($this);
    }

    public function __destruct()
    {
        if ($this->part !== null) {
            $this->part->detach($this);
        }
    }

    public function update(SplSubject $subject) : void
    {
        if ($this->appendStream !== null) {
            unset($this->stream);
            $this->appendStream = null;
        }
    }

    private function getCharsetDecoratorForStream(StreamInterface $stream) : StreamInterface
    {
        $charset = $this->part->getCharset();
        if (!empty($charset)) {
            $stream = $this->streamFactory->newCharsetStream(
                $stream,
                $charset,
                MailMimeParser::DEFAULT_CHARSET
            );
        }
        return $stream;
    }

    private function getTransferEncodingDecoratorForStream(StreamInterface $stream) : StreamInterface
    {
        $encoding = $this->part->getContentTransferEncoding();
        $decorator = null;
        switch ($encoding) {
            case 'quoted-printable':
                $decorator = $this->streamFactory->newQuotedPrintableStream($stream);
                break;
            case 'base64':
                $decorator = $this->streamFactory->newBase64Stream(
                    $this->streamFactory->newChunkSplitStream($stream)
                );
                break;
            case 'x-uuencode':
                $decorator = $this->streamFactory->newUUStream($stream);
                $decorator->setFilename($this->part->getFilename());
                break;
            default:
                return $stream;
        }
        return $decorator;
    }

    private function writePartContentTo(StreamInterface $stream) : self
    {
        $contentStream = $this->part->getContentStream();
        if ($contentStream !== null) {
            $copyStream = $this->streamFactory->newNonClosingStream($stream);
            $es = $this->getTransferEncodingDecoratorForStream($copyStream);
            $cs = $this->getCharsetDecoratorForStream($es);
            Psr7\Utils::copyToStream($contentStream, $cs);
            $cs->close();
        }
        return $this;
    }

    protected function getBoundaryAndChildStreams(IMimePart $part) : array
    {
        $boundary = $part->getHeaderParameter(HeaderConsts::CONTENT_TYPE, 'boundary');
        if ($boundary === null) {
            return \array_map(
                function($child) {
                    return $child->getStream();
                },
                $part->getChildParts()
            );
        }
        $streams = [];
        foreach ($part->getChildParts() as $i => $child) {
            if ($i !== 0 || $part->hasContent()) {
                $streams[] = Psr7\Utils::streamFor("\r\n");
            }
            $streams[] = Psr7\Utils::streamFor("--$boundary\r\n");
            $streams[] = $child->getStream();
        }
        $streams[] = Psr7\Utils::streamFor("\r\n--$boundary--\r\n");

        return $streams;
    }

    protected function getStreamsArray() : array
    {
        $content = Psr7\Utils::streamFor();
        $this->writePartContentTo($content);
        $content->rewind();
        $streams = [$this->streamFactory->newHeaderStream($this->part), $content];

        if ($this->part instanceof IMimePart && $this->part->getChildCount() > 0) {
            $streams = \array_merge($streams, $this->getBoundaryAndChildStreams($this->part));
        }

        return $streams;
    }

    protected function createStream() : StreamInterface
    {
        if ($this->appendStream === null) {
            $this->appendStream = new AppendStream($this->getStreamsArray());
        }
        return $this->appendStream;
    }
}
