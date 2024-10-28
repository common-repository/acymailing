<?php

namespace ZBateson\MailMimeParser\Message;

use GuzzleHttp\Psr7\CachingStream;
use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Stream\StreamFactory;

class PartStreamContainer
{
    protected $streamFactory;

    protected $stream;

    protected $contentStream;

    protected $decodedStream;

    protected $charsetStream;

    protected $detachParsedStream;

    private $encoding = [
        'type' => null,
        'filter' => null
    ];

    private $charset = [
        'from' => null,
        'to' => null,
        'filter' => null
    ];

    public function __construct(StreamFactory $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    public function setStream(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function getStream()
    {
        $this->stream->rewind();
        return $this->stream;
    }

    public function hasContent() : bool
    {
        return ($this->contentStream !== null);
    }

    public function setContentStream(?StreamInterface $contentStream = null)
    {
        $this->contentStream = $contentStream;
        $this->decodedStream = null;
        $this->charsetStream = null;
    }

    private function isTransferEncodingFilterChanged(?string $transferEncoding) : bool
    {
        return ($transferEncoding !== $this->encoding['type']);
    }

    private function isCharsetFilterChanged(string $fromCharset, string $toCharset) : bool
    {
        return ($fromCharset !== $this->charset['from']
            || $toCharset !== $this->charset['to']);
    }

    protected function attachTransferEncodingFilter(?string $transferEncoding) : self
    {
        if ($this->decodedStream !== null) {
            $this->encoding['type'] = $transferEncoding;
            $assign = null;
            switch ($transferEncoding) {
                case 'base64':
                    $assign = $this->streamFactory->newBase64Stream($this->decodedStream);
                    break;
                case 'x-uuencode':
                    $assign = $this->streamFactory->newUUStream($this->decodedStream);
                    break;
                case 'quoted-printable':
                    $assign = $this->streamFactory->newQuotedPrintableStream($this->decodedStream);
                    break;
            }
            if ($assign !== null) {
                $this->decodedStream = new CachingStream($assign);
            }
        }
        return $this;
    }

    protected function attachCharsetFilter(string $fromCharset, string $toCharset) : self
    {
        if ($this->charsetStream !== null) {
            $this->charsetStream = new CachingStream($this->streamFactory->newCharsetStream(
                $this->charsetStream,
                $fromCharset,
                $toCharset
            ));
            $this->charset['from'] = $fromCharset;
            $this->charset['to'] = $toCharset;
        }
        return $this;
    }

    private function resetCharsetStream() : self
    {
        $this->charset = [
            'from' => null,
            'to' => null,
            'filter' => null
        ];
        $this->decodedStream->rewind();
        $this->charsetStream = $this->decodedStream;
        return $this;
    }

    public function reset()
    {
        $this->encoding = [
            'type' => null,
            'filter' => null
        ];
        $this->charset = [
            'from' => null,
            'to' => null,
            'filter' => null
        ];
        $this->contentStream->rewind();
        $this->decodedStream = $this->contentStream;
        $this->charsetStream = $this->contentStream;
    }

    public function getContentStream(?string $transferEncoding, ?string $fromCharset, ?string $toCharset)
    {
        if ($this->contentStream === null) {
            return null;
        }
        if (empty($fromCharset) || empty($toCharset)) {
            return $this->getBinaryContentStream($transferEncoding);
        }
        if ($this->charsetStream === null
            || $this->isTransferEncodingFilterChanged($transferEncoding)
            || $this->isCharsetFilterChanged($fromCharset, $toCharset)) {
            if ($this->charsetStream === null
                || $this->isTransferEncodingFilterChanged($transferEncoding)) {
                $this->reset();
                $this->attachTransferEncodingFilter($transferEncoding);
            }
            $this->resetCharsetStream();
            $this->attachCharsetFilter($fromCharset, $toCharset);
        }
        $this->charsetStream->rewind();
        return $this->charsetStream;
    }

    public function getBinaryContentStream(?string $transferEncoding = null) : ?StreamInterface
    {
        if ($this->contentStream === null) {
            return null;
        }
        if ($this->decodedStream === null
            || $this->isTransferEncodingFilterChanged($transferEncoding)) {
            $this->reset();
            $this->attachTransferEncodingFilter($transferEncoding);
        }
        $this->decodedStream->rewind();
        return $this->decodedStream;
    }
}
