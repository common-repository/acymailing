<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

class LiteralPart extends HeaderPart
{
    public function __construct(MbWrapper $charsetConverter, $token = null)
    {
        parent::__construct($charsetConverter);
        $this->value = $token;
        if ($token !== null) {
            $this->value = \preg_replace('/\r|\n/', '', $this->convertEncoding($token));
        }
    }
}
