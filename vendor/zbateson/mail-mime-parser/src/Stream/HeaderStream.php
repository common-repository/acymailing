<?php

namespace ZBateson\MailMimeParser\Stream;

use ArrayIterator;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use SplObserver;
use SplSubject;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\IMimePart;

#[\AllowDynamicProperties]
class HeaderStream implements SplObserver, StreamInterface
{
    use StreamDecoratorTrait;

    protected $part;

    public function __construct(IMessagePart $part)
    {
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
        if ($this->stream !== null) {
            $this->stream = $this->createStream();
        }
    }

    private function getPartHeadersIterator() : \Iterator
    {
        if ($this->part instanceof IMimePart) {
            return $this->part->getRawHeaderIterator();
        } elseif ($this->part->getParent() !== null && $this->part->getParent()->isMime()) {
            return new ArrayIterator([
                [HeaderConsts::CONTENT_TYPE, $this->part->getContentType()],
                [HeaderConsts::CONTENT_DISPOSITION, $this->part->getContentDisposition()],
                [HeaderConsts::CONTENT_TRANSFER_ENCODING, $this->part->getContentTransferEncoding()]
            ]);
        }
        return new ArrayIterator();
    }

    public function writePartHeadersTo(StreamInterface $stream) : self
    {
        foreach ($this->getPartHeadersIterator() as $header) {
            $stream->write("{$header[0]}: {$header[1]}\r\n");
        }
        $stream->write("\r\n");
        return $this;
    }

    protected function createStream() : StreamInterface
    {
        $stream = Psr7\Utils::streamFor();
        $this->writePartHeadersTo($stream);
        $stream->rewind();
        return $stream;
    }
}
