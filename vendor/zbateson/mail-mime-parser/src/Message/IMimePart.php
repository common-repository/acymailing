<?php

namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Header\IHeader;

interface IMimePart extends IMultiPart
{
    public function isMultiPart();

    public function isSignaturePart();

    public function getHeader($name, $offset = 0);

    public function getHeaderAs(string $name, string $iHeaderClass, int $offset = 0) : ?IHeader;

    public function getAllHeaders();

    public function getAllHeadersByName($name);

    public function getRawHeaders();

    public function getRawHeaderIterator();

    public function getHeaderValue($name, $defaultValue = null);

    public function getHeaderParameter($header, $param, $defaultValue = null);

    public function setRawHeader(string $name, ?string $value, int $offset = 0);

    public function addRawHeader(string $name, string $value);

    public function removeHeader(string $name);

    public function removeSingleHeader(string $name, int $offset = 0);
}
