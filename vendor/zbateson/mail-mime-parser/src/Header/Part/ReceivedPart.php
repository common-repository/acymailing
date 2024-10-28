<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

class ReceivedPart extends ParameterPart
{
    public function __construct(MbWrapper $charsetConverter, $name, $value) {
        parent::__construct($charsetConverter, '', '');
        $this->name = \trim($name);
        $this->value = $value ? \trim($value) : $value;
    }
}
