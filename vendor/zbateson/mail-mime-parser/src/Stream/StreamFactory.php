<?php

namespace ZBateson\MailMimeParser\Stream;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\StreamDecorators\Base64Stream;
use ZBateson\StreamDecorators\CharsetStream;
use ZBateson\StreamDecorators\ChunkSplitStream;
use ZBateson\StreamDecorators\NonClosingStream;
use ZBateson\StreamDecorators\PregReplaceFilterStream;
use ZBateson\StreamDecorators\QuotedPrintableStream;
use ZBateson\StreamDecorators\SeekingLimitStream;
use ZBateson\StreamDecorators\UUStream;

class StreamFactory
{
    public function getLimitedPartStream(PartBuilder $part)
    {
        return $this->newLimitStream(
            $part->getStream(),
            $part->getStreamPartLength(),
            $part->getStreamPartStartPos()
        );
    }

    public function getLimitedContentStream(PartBuilder $part)
    {
        $length = $part->getStreamContentLength();
        if ($length !== 0) {
            return $this->newLimitStream(
                $part->getStream(),
                $part->getStreamContentLength(),
                $part->getStreamContentStartPos()
            );
        }
        return null;
    }

    private function newLimitStream(StreamInterface $stream, int $length, int $start) : SeekingLimitStream
    {
        return new SeekingLimitStream(
            $this->newNonClosingStream($stream),
            $length,
            $start
        );
    }

    public function newNonClosingStream(StreamInterface $stream)
    {
        return new NonClosingStream($stream);
    }

    public function newChunkSplitStream(StreamInterface $stream)
    {
        return new ChunkSplitStream($stream);
    }

    public function newBase64Stream(StreamInterface $stream)
    {
        return new Base64Stream(
            new PregReplaceFilterStream($stream, '/[^a-zA-Z0-9\/\+=]/', '')
        );
    }

    public function newQuotedPrintableStream(StreamInterface $stream)
    {
        return new QuotedPrintableStream($stream);
    }

    public function newUUStream(StreamInterface $stream)
    {
        return new UUStream($stream);
    }

    public function newCharsetStream(StreamInterface $stream, string $fromCharset, string $toCharset)
    {
        return new CharsetStream($stream, $fromCharset, $toCharset);
    }

    public function newMessagePartStream(IMessagePart $part)
    {
        return new MessagePartStream($this, $part);
    }

    public function newHeaderStream(IMessagePart $part)
    {
        return new HeaderStream($part);
    }
}
