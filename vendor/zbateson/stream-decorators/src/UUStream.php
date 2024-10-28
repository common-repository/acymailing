<?php

namespace ZBateson\StreamDecorators;

use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class UUStream implements StreamInterface
{
    use StreamDecoratorTrait;

    protected $filename = null;

    private $buffer;

    private $remainder = '';

    private $position = 0;

    private $isWriting = false;

    private $stream;

    public function __construct(StreamInterface $stream, ?string $filename = null)
    {
        $this->stream = $stream;
        $this->filename = $filename;
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
        throw new RuntimeException('Cannot seek a UUStream');
    }

    public function isSeekable() : bool
    {
        return false;
    }

    private function readToEndOfLine(int $length) : string
    {
        $str = $this->stream->read($length);
        if ($str === '') {
            return $str;
        }
        while (\substr($str, -1) !== "\n") {
            $chr = $this->stream->read(1);
            if ($chr === '') {
                break;
            }
            $str .= $chr;
        }
        return $str;
    }

    private function filterAndDecode(string $str) : string
    {
        $ret = \str_replace("\r", '', $str);
        $ret = \preg_replace('/[^\x21-\xf5`\n]/', '`', $ret);
        if ($this->position === 0) {
            $matches = [];
            if (\preg_match('/^\s*begin\s+[^\s+]\s+([^\r\n]+)\s*$/im', $ret, $matches)) {
                $this->filename = $matches[1];
            }
            $ret = \preg_replace('/^\s*begin[^\r\n]+\s*$/im', '', $ret);
        } else {
            $ret = \preg_replace('/^\s*end\s*$/im', '', $ret);
        }
        return \convert_uudecode(\trim($ret));
    }

    private function fillBuffer(int $length) : void
    {
        while ($this->buffer->getSize() < $length) {
            $read = $this->readToEndOfLine(5040);
            if ($read === '') {
                break;
            }
            $this->buffer->write($this->filterAndDecode($read));
        }
    }

    public function eof() : bool
    {
        return ($this->buffer->eof() && $this->stream->eof());
    }

    public function read($length) : string
    {
        if ($length <= 0 || $this->eof()) {
            return $this->stream->read($length);
        }
        $this->fillBuffer($length);
        $read = $this->buffer->read($length);
        $this->position += \strlen($read);
        return $read;
    }

    private function writeUUHeader() : void
    {
        $filename = (empty($this->filename)) ? 'null' : $this->filename;
        $this->stream->write("begin 666 $filename");
    }

    private function writeUUFooter() : void
    {
        $this->stream->write("\r\n`\r\nend\r\n");
    }

    private function writeEncoded(string $bytes) : void
    {
        $encoded = \preg_replace('/\r\n|\r|\n/', "\r\n", \rtrim(\convert_uuencode($bytes)));
        $this->stream->write("\r\n" . \rtrim(\substr($encoded, 0, -1)));
    }

    private function handleRemainder(string $string) : string
    {
        $write = $this->remainder . $string;
        $nRem = \strlen($write) % 45;
        $this->remainder = '';
        if ($nRem !== 0) {
            $this->remainder = \substr($write, -$nRem);
            $write = \substr($write, 0, -$nRem);
        }
        return $write;
    }

    public function write($string) : int
    {
        $this->isWriting = true;
        if ($this->position === 0) {
            $this->writeUUHeader();
        }
        $write = $this->handleRemainder($string);
        if ($write !== '') {
            $this->writeEncoded($write);
        }
        $written = \strlen($string);
        $this->position += $written;
        return $written;
    }

    public function getFilename() : string
    {
        return $this->filename;
    }

    public function setFilename(string $filename) : void
    {
        $this->filename = $filename;
    }

    private function beforeClose() : void
    {
        if (!$this->isWriting) {
            return;
        }
        if ($this->remainder !== '') {
            $this->writeEncoded($this->remainder);
        }
        $this->remainder = '';
        $this->isWriting = false;
        $this->writeUUFooter();
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
