<?php

namespace ZBateson\StreamDecorators;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

class ChunkSplitStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private $position;

    private $lineLength;

    private $lineEnding;

    private $lineEndingLength;

    private $stream;

    public function __construct(StreamInterface $stream, int $lineLength = 76, string $lineEnding = "\r\n")
    {
        $this->stream = $stream;
        $this->lineLength = $lineLength;
        $this->lineEnding = $lineEnding;
        $this->lineEndingLength = \strlen($this->lineEnding);
    }

    private function getChunkedString(string $string) : string
    {
        $firstLine = '';
        if ($this->tell() !== 0) {
            $next = $this->lineLength - ($this->position % ($this->lineLength + $this->lineEndingLength));
            if (\strlen($string) > $next) {
                $firstLine = \substr($string, 0, $next) . $this->lineEnding;
                $string = \substr($string, $next);
            }
        }
        $chunked = $firstLine . \chunk_split($string, $this->lineLength, $this->lineEnding);
        return \substr($chunked, 0, \strlen($chunked) - $this->lineEndingLength);
    }

    public function write($string) : int
    {
        $chunked = $this->getChunkedString($string);
        $this->position += \strlen($chunked);
        return $this->stream->write($chunked);
    }

    private function beforeClose() : void
    {
        if ($this->position !== 0) {
            $this->stream->write($this->lineEnding);
        }
    }

    public function close() : void
    {
        $this->beforeClose();
        $this->stream->close();
    }

    public function detach()
    {
        $this->beforeClose();
        $this->stream->detach();

        return null;
    }
}
