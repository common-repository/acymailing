<?php

namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\IHeader;
use ZBateson\MailMimeParser\Header\ParameterHeader;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;

class MimePart extends MultiPart implements IMimePart
{
    protected $headerContainer;

    public function __construct(
        ?IMimePart $parent = null,
        ?PartStreamContainer $streamContainer = null,
        ?PartHeaderContainer $headerContainer = null,
        ?PartChildrenContainer $partChildrenContainer = null
    ) {
        $setStream = false;
        $di = MailMimeParser::getDependencyContainer();
        if ($streamContainer === null || $headerContainer === null || $partChildrenContainer === null) {
            $headerContainer = $di[\ZBateson\MailMimeParser\Message\PartHeaderContainer::class];
            $streamContainer = $di[\ZBateson\MailMimeParser\Message\PartStreamContainer::class];
            $partChildrenContainer = $di[\ZBateson\MailMimeParser\Message\PartChildrenContainer::class];
            $setStream = true;
        }
        parent::__construct(
            $parent,
            $streamContainer,
            $partChildrenContainer
        );
        if ($setStream) {
            $streamFactory = $di[\ZBateson\MailMimeParser\Stream\StreamFactory::class];
            $streamContainer->setStream($streamFactory->newMessagePartStream($this));
        }
        $this->headerContainer = $headerContainer;
    }

    public function getFilename() : ?string
    {
        return $this->getHeaderParameter(
            HeaderConsts::CONTENT_DISPOSITION,
            'filename',
            $this->getHeaderParameter(
                HeaderConsts::CONTENT_TYPE,
                'name'
            )
        );
    }

    public function isMime() : bool
    {
        return true;
    }

    public function isMultiPart()
    {
        return (bool) (\preg_match(
            '~multipart/.*~i',
            $this->getContentType()
        ));
    }

    public function isTextPart() : bool
    {
        return ($this->getCharset() !== null);
    }

    public function getContentType(string $default = 'text/plain') : ?string
    {
        return \strtolower($this->getHeaderValue(HeaderConsts::CONTENT_TYPE, $default));
    }

    public function getCharset() : ?string
    {
        $charset = $this->getHeaderParameter(HeaderConsts::CONTENT_TYPE, 'charset');
        if ($charset === null || \strcasecmp($charset, 'binary') === 0) {
            $contentType = $this->getContentType();
            if ($contentType === 'text/plain' || $contentType === 'text/html') {
                return 'ISO-8859-1';
            }
            return null;
        }
        return \strtoupper($charset);
    }

    public function getContentDisposition(?string $default = 'inline') : ?string
    {
        $value = $this->getHeaderValue(HeaderConsts::CONTENT_DISPOSITION);
        if ($value === null || !\in_array($value, ['inline', 'attachment'])) {
            return $default;
        }
        return \strtolower($value);
    }

    public function getContentTransferEncoding(?string $default = '7bit') : ?string
    {
        static $translated = [
            'x-uue' => 'x-uuencode',
            'uue' => 'x-uuencode',
            'uuencode' => 'x-uuencode'
        ];
        $type = \strtolower($this->getHeaderValue(HeaderConsts::CONTENT_TRANSFER_ENCODING, $default));
        if (isset($translated[$type])) {
            return $translated[$type];
        }
        return $type;
    }

    public function getContentId() : ?string
    {
        return $this->getHeaderValue(HeaderConsts::CONTENT_ID);
    }

    public function isSignaturePart()
    {
        if ($this->parent === null || !$this->parent instanceof IMessage) {
            return false;
        }
        return $this->parent->getSignaturePart() === $this;
    }

    public function getHeader($name, $offset = 0)
    {
        return $this->headerContainer->get($name, $offset);
    }

    public function getHeaderAs(string $name, string $iHeaderClass, int $offset = 0) : ?IHeader
    {
        return $this->headerContainer->getAs($name, $iHeaderClass, $offset);
    }

    public function getAllHeaders()
    {
        return $this->headerContainer->getHeaderObjects();
    }

    public function getAllHeadersByName($name)
    {
        return $this->headerContainer->getAll($name);
    }

    public function getRawHeaders()
    {
        return $this->headerContainer->getHeaders();
    }

    public function getRawHeaderIterator()
    {
        return $this->headerContainer->getIterator();
    }

    public function getHeaderValue($name, $defaultValue = null)
    {
        $header = $this->getHeader($name);
        if ($header !== null) {
            return $header->getValue() ?: $defaultValue;
        }
        return $defaultValue;
    }

    public function getHeaderParameter($header, $param, $defaultValue = null)
    {
        $obj = $this->getHeader($header);
        if ($obj && $obj instanceof ParameterHeader) {
            return $obj->getValueFor($param, $defaultValue);
        }
        return $defaultValue;
    }

    public function setRawHeader(string $name, ?string $value, int $offset = 0)
    {
        $this->headerContainer->set($name, $value, $offset);
        $this->notify();
        return $this;
    }

    public function addRawHeader(string $name, string $value)
    {
        $this->headerContainer->add($name, $value);
        $this->notify();
        return $this;
    }

    public function removeHeader(string $name)
    {
        $this->headerContainer->removeAll($name);
        $this->notify();
        return $this;
    }

    public function removeSingleHeader(string $name, int $offset = 0)
    {
        $this->headerContainer->remove($name, $offset);
        $this->notify();
        return $this;
    }
}
