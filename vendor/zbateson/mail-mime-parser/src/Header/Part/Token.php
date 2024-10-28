<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

class Token extends HeaderPart
{
    public function __construct(MbWrapper $charsetConverter, $value)
    {
        parent::__construct($charsetConverter);
        $this->value = $value;
    }

    public function isSpace()
    {
        return (\preg_match('/^\s+$/', $this->value) === 1);
    }

    public function ignoreSpacesBefore() : bool
    {
        return $this->isSpace();
    }

    public function ignoreSpacesAfter() : bool
    {
        return $this->isSpace();
    }
}
