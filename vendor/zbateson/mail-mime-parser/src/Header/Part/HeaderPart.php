<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MbWrapper\MbWrapper;

abstract class HeaderPart implements IHeaderPart
{
    protected $value;

    protected $charsetConverter;

    public function __construct(MbWrapper $charsetConverter)
    {
        $this->charsetConverter = $charsetConverter;
    }

    public function getValue() : ?string
    {
        return $this->value;
    }

    public function __toString() : string
    {
        return $this->value;
    }

    public function ignoreSpacesBefore() : bool
    {
        return false;
    }

    public function ignoreSpacesAfter() : bool
    {
        return false;
    }

    protected function convertEncoding(string $str, string $from = 'ISO-8859-1', bool $force = false) : string
    {
        if ($from !== 'UTF-8') {
            if ($force || !($this->charsetConverter->checkEncoding($str, 'UTF-8'))) {
                return $this->charsetConverter->convert($str, $from, 'UTF-8');
            }
        }
        return $str;
    }
}
