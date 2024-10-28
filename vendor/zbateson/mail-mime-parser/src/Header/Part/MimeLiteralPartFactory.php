<?php

namespace ZBateson\MailMimeParser\Header\Part;

class MimeLiteralPartFactory extends HeaderPartFactory
{
    public function newInstance(string $value)
    {
        return $this->newMimeLiteralPart($value);
    }
}
