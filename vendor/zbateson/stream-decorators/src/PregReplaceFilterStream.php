<?php

namespace ZBateson\StreamDecorators;

use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class PregReplaceFilterStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private $pattern;

    private $replacement;

    private $buffer;

    private $stream;

    public function __construct(StreamInterface $stream, string $pattern, string $replacement)
    {
        $this->stream = $stream;
        $this->pattern = $pattern;
        $this->replacement = $replacement;
        $this->buffer = new BufferStream();
    }

    public function eof() : bool
    {
        return ($this->buffer->eof() && $this->stream->eof());
    }

    public function seek($offset, $whence = SEEK_SET) : void
    {
        throw new RuntimeException('Cannot seek a PregReplaceFilterStream');
    }

    public function isSeekable() : bool
    {
        return false;
    }

    private function fillBuffer(int $length) : void
    {
        $fill = (int) \max([$length, 8192]);
        while ($this->buffer->getSize() < $length) {
            $read = $this->stream->read($fill);
            if ($read === '') {
                break;
            }
            $this->buffer->write(\preg_replace($this->pattern, $this->replacement, $read));
        }
    }

    public function read($length) : string
    {
        $this->fillBuffer($length);
        return $this->buffer->read($length);
    }
}
