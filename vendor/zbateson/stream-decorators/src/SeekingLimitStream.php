<?php

namespace ZBateson\StreamDecorators;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

class SeekingLimitStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private $offset;

    private $limit;

    private $position = 0;

    private $stream;

    public function __construct(StreamInterface $stream, int $limit = -1, int $offset = 0)
    {
        $this->stream = $stream;
        $this->setLimit($limit);
        $this->setOffset($offset);
    }

    public function tell() : int
    {
        return $this->position;
    }

    public function getSize() : ?int
    {
        $size = $this->stream->getSize();
        if ($size === null) {
            $pos = $this->stream->tell();
            $this->stream->seek(0, SEEK_END);
            $size = $this->stream->tell();
            $this->stream->seek($pos);
        }
        if ($this->limit === -1) {
            return $size - $this->offset;
        }

        return \min([$this->limit, $size - $this->offset]);
    }

    public function eof() : bool
    {
        $size = $this->limit;
        if ($size === -1) {
            $size = $this->getSize();
        }
        return ($this->position >= $size);
    }

    private function doSeek(int $pos) : void
    {
        if ($this->limit !== -1) {
            $pos = \min([$pos, $this->limit]);
        }
        $this->position = \max([0, $pos]);
    }

    public function seek($offset, $whence = SEEK_SET) : void
    {
        $pos = $offset;
        switch ($whence) {
            case SEEK_CUR:
                $pos = $this->position + $offset;
                break;
            case SEEK_END:
                $pos = $this->limit + $offset;
                break;
            default:
                break;
        }
        $this->doSeek($pos);
    }

    public function setOffset(int $offset) : void
    {
        $this->offset = $offset;
        $this->position = 0;
    }

    public function setLimit(int $limit) : void
    {
        $this->limit = $limit;
    }

    public function seekAndRead(int $length) : string
    {
        $this->stream->seek($this->offset + $this->position);
        if ($this->limit !== -1) {
            $length = \min($length, $this->limit - $this->position);
            if ($length <= 0) {
                return '';
            }
        }
        return $this->stream->read($length);
    }

    public function read($length) : string
    {
        $pos = $this->stream->tell();
        $ret = $this->seekAndRead($length);
        $this->position += \strlen($ret);
        $this->stream->seek($pos);
        if ($this->limit !== -1 && $this->position > $this->limit) {
            $ret = \substr($ret, 0, -($this->position - $this->limit));
            $this->position = $this->limit;
        }
        return $ret;
    }
}
