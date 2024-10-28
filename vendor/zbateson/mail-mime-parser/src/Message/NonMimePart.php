<?php

namespace ZBateson\MailMimeParser\Message;

abstract class NonMimePart extends MessagePart
{
    public function isTextPart() : bool
    {
        return true;
    }

    public function getContentType(string $default = 'text/plain') : ?string
    {
        return $default;
    }

    public function getCharset() : ?string
    {
        return 'ISO-8859-1';
    }

    public function getContentDisposition(?string $default = 'inline') : ?string
    {
        return 'inline';
    }

    public function getContentTransferEncoding(?string $default = '7bit') : ?string
    {
        return '7bit';
    }

    public function isMime() : bool
    {
        return false;
    }

    public function getContentId() : ?string
    {
        return null;
    }
}
