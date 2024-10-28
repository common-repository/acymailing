<?php

namespace ZBateson\StreamDecorators;

use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Base64Stream implements StreamInterface
{
    use StreamDecoratorTrait;

    private $buffer;

    private $remainder = '';

    private $position = 0;

    private $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
        $this->buffer = new BufferStream();
    }

    public function tell() : int
    {
        return $this->position;
    }

    public function getSize() : ?int
    {
        return null;
    }

    public function seek($offset, $whence = SEEK_SET) : void
    {
        throw new RuntimeException('Cannot seek a Base64Stream');
    }

    public function isSeekable() : bool
    {
        return false;
    }

    public function eof() : bool
    {
        return ($this->buffer->eof() && $this->stream->eof());
    }

    private function fillBuffer(int $length) : void
    {
        $fill = 8192;
        while ($this->buffer->getSize() < $length) {
            $read = $this->stream->read($fill);
            if ($read === '') {
                break;
            }
            $this->buffer->write(\base64_decode($read));
        }
    }

    public function read($length) : string
    {
        if ($length <= 0 || $this->eof()) {
            return $this->stream->read($length);
        }
        $this->fillBuffer($length);
        $ret = $this->buffer->read($length);
        $this->position += \strlen($ret);
        return $ret;
    }

    public function write($string) : int
    {
        $bytes = $this->remainder . $string;
        $len = \strlen($bytes);
        if (($len % 3) !== 0) {
            $this->remainder = \substr($bytes, -($len % 3));
            $bytes = \substr($bytes, 0, $len - ($len % 3));
        } else {
            $this->remainder = '';
        }
        $this->stream->write(\base64_encode($bytes));
        $written = \strlen($string);
        $this->position += $len;
        return $written;
    }

    private function beforeClose() : void
    {
        if ($this->isWritable() && $this->remainder !== '') {
            $this->stream->write(\base64_encode($this->remainder));
            $this->remainder = '';
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
