<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

class AddressPart extends ParameterPart
{
    public function __construct(MbWrapper $charsetConverter, string $name, string $email)
    {
        parent::__construct(
            $charsetConverter,
            $name,
            ''
        );
        $this->value = $this->convertEncoding($email);
    }

    public function getEmail() : string
    {
        return $this->value;
    }
}
