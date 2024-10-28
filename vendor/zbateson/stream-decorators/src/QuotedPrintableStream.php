<?php

namespace ZBateson\StreamDecorators;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class QuotedPrintableStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private $position = 0;

    private $lastLine = '';

    private $stream;

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
        throw new RuntimeException('Cannot seek a QuotedPrintableStream');
    }

    public function isSeekable() : bool
    {
        return false;
    }

    private function readEncodedChars(int $length, string $pre = '') : string
    {
        $str = $pre . $this->stream->read($length);
        $len = \strlen($str);
        if ($len > 0 && !\preg_match('/^[0-9a-f]{2}$|^[\r\n]{1,2}.?$/is', $str) && $this->stream->isSeekable()) {
            $this->stream->seek(-$len, SEEK_CUR);
            return '3D';    // '=' character
        }
        return $str;
    }

    private function decodeBlock(string $block) : string
    {
        if (\substr($block, -1) === '=') {
            $block .= $this->readEncodedChars(2);
        } elseif (\substr($block, -2, 1) === '=') {
            $first = \substr($block, -1);
            $block = \substr($block, 0, -1);
            $block .= $this->readEncodedChars(1, $first);
        }
        return \quoted_printable_decode($block);
    }

    private function readRawDecodeAndAppend(int $length, string &$str) : int
    {
        $block = $this->stream->read($length);
        if ($block === '') {
            return -1;
        }
        $decoded = $this->decodeBlock($block);
        $count = \strlen($decoded);
        $str .= $decoded;
        return $count;
    }

    public function read($length) : string
    {
        if ($length <= 0 || $this->eof()) {
            return $this->stream->read($length);
        }
        $count = 0;
        $bytes = '';
        while ($count < $length) {
            $nRead = $this->readRawDecodeAndAppend($length - $count, $bytes);
            if ($nRead === -1) {
                break;
            }
            $this->position += $nRead;
            $count += $nRead;
        }
        return $bytes;
    }

    public function write($string) : int
    {
        $encodedLine = \quoted_printable_encode($this->lastLine);
        $lineAndString = \rtrim(\quoted_printable_encode($this->lastLine . $string), "\r\n");
        $write = \substr($lineAndString, \strlen($encodedLine));
        $this->stream->write($write);
        $written = \strlen($string);
        $this->position += $written;

        $lpos = \strrpos($lineAndString, "\n");
        $lastLine = $lineAndString;
        if ($lpos !== false) {
            $lastLine = \substr($lineAndString, $lpos + 1);
        }
        $this->lastLine = \quoted_printable_decode($lastLine);
        return $written;
    }

    private function beforeClose() : void
    {
        if ($this->isWritable() && $this->lastLine !== '') {
            $this->stream->write("\r\n");
            $this->lastLine = '';
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
