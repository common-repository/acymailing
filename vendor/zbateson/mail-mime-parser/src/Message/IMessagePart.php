<?php

namespace ZBateson\MailMimeParser\Message;

use Psr\Http\Message\StreamInterface;
use SplSubject;
use ZBateson\MailMimeParser\MailMimeParser;

interface IMessagePart extends SplSubject
{
    public function getParent();

    public function hasContent() : bool;

    public function isTextPart() : bool;

    public function getContentType(string $default = 'text/plain') : ?string;

    public function getCharset() : ?string;

    public function getContentDisposition(?string $default = null) : ?string;

    public function getContentTransferEncoding(?string $default = null) : ?string;

    public function getContentId() : ?string;

    public function getFilename() : ?string;

    public function isMime() : bool;

    public function setCharsetOverride(string $charsetOverride, bool $onlyIfNoCharset = false);

    public function getContentStream(string $charset = MailMimeParser::DEFAULT_CHARSET);

    public function getBinaryContentStream();

    public function getBinaryContentResourceHandle();

    public function saveContent($filenameResourceOrStream);

    public function getContent(string $charset = MailMimeParser::DEFAULT_CHARSET) : ?string;

    public function attachContentStream(StreamInterface $stream, string $streamCharset = MailMimeParser::DEFAULT_CHARSET);

    public function detachContentStream();

    public function setContent($resource, string $resourceCharset = MailMimeParser::DEFAULT_CHARSET);

    public function getResourceHandle();

    public function getStream();

    public function save($filenameResourceOrStream, string $filemode = 'w+');

    public function __toString() : string;
}
