<?php

namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\MailMimeParser;

class UUEncodedPart extends NonMimePart implements IUUEncodedPart
{
    protected $mode = null;

    protected $filename = null;

    public function __construct(?int $mode = null, ?string $filename = null, ?IMimePart $parent = null, ?PartStreamContainer $streamContainer = null)
    {
        if ($streamContainer === null) {
            $di = MailMimeParser::getDependencyContainer();
            $streamContainer = $di[\ZBateson\MailMimeParser\Message\PartStreamContainer::class];
            $streamFactory = $di[\ZBateson\MailMimeParser\Stream\StreamFactory::class];
            $streamContainer->setStream($streamFactory->newMessagePartStream($this));
        }
        parent::__construct(
            $streamContainer,
            $parent
        );
        $this->mode = $mode;
        $this->filename = $filename;
    }

    public function getFilename() : ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
        $this->notify();
        return $this;
    }

    public function isTextPart() : bool
    {
        return false;
    }

    public function getContentType(string $default = 'application/octet-stream') : ?string
    {
        return 'application/octet-stream';
    }

    public function getCharset() : ?string
    {
        return null;
    }

    public function getContentDisposition(?string $default = 'attachment') : ?string
    {
        return 'attachment';
    }

    public function getContentTransferEncoding(?string $default = 'x-uuencode') : ?string
    {
        return 'x-uuencode';
    }

    public function getUnixFileMode() : ?int
    {
        return $this->mode;
    }

    public function setUnixFileMode(int $mode)
    {
        $this->mode = $mode;
        $this->notify();
        return $this;
    }
}
