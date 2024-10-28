<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

class AddressGroupPart extends MimeLiteralPart
{
    protected $addresses;

    public function __construct(MbWrapper $charsetConverter, array $addresses, string $name = '')
    {
        parent::__construct($charsetConverter, \trim($name));
        $this->addresses = $addresses;
    }

    public function getAddresses() : array
    {
        return $this->addresses;
    }

    public function getAddress(int $index)
    {
        if (!isset($this->addresses[$index])) {
            return null;
        }
        return $this->addresses[$index];
    }

    public function getName() : string
    {
        return $this->value;
    }
}
