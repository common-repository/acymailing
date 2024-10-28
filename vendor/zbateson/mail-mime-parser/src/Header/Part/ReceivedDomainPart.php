<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

class ReceivedDomainPart extends ReceivedPart
{
    protected $ehloName;

    protected $hostname;

    protected $address;

    public function __construct(MbWrapper $charsetConverter, $name, $value, $ehloName = null, $hostname = null, $address = null) {
        parent::__construct($charsetConverter, $name, $value);
        $this->ehloName = $ehloName;
        $this->hostname = $hostname;
        $this->address = $address;
    }

    public function getEhloName()
    {
        return $this->ehloName;
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    public function getAddress() : ?string
    {
        return $this->address;
    }
}
