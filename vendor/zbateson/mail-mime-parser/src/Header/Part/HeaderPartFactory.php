<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MbWrapper\MbWrapper;

class HeaderPartFactory
{
    protected $charsetConverter;

    public function __construct(MbWrapper $charsetConverter)
    {
        $this->charsetConverter = $charsetConverter;
    }

    public function newInstance(string $value)
    {
        return $this->newToken($value);
    }

    public function newToken(string $value)
    {
        return new Token($this->charsetConverter, $value);
    }

    public function newSplitParameterToken($name)
    {
        return new SplitParameterToken($this->charsetConverter, $name);
    }

    public function newLiteralPart($value)
    {
        return new LiteralPart($this->charsetConverter, $value);
    }

    public function newMimeLiteralPart($value)
    {
        return new MimeLiteralPart($this->charsetConverter, $value);
    }

    public function newCommentPart($value)
    {
        return new CommentPart($this->charsetConverter, $value);
    }

    public function newAddressPart($name, $email)
    {
        return new AddressPart($this->charsetConverter, $name, $email);
    }

    public function newAddressGroupPart(array $addresses, $name = '')
    {
        return new AddressGroupPart($this->charsetConverter, $addresses, $name);
    }

    public function newDatePart($value)
    {
        return new DatePart($this->charsetConverter, $value);
    }

    public function newParameterPart($name, $value, $language = null)
    {
        return new ParameterPart($this->charsetConverter, $name, $value, $language);
    }

    public function newReceivedPart($name, $value)
    {
        return new ReceivedPart($this->charsetConverter, $name, $value);
    }

    public function newReceivedDomainPart(
        $name,
        $value,
        $ehloName = null,
        $hostName = null,
        $hostAddress = null
    ) {
        return new ReceivedDomainPart(
            $this->charsetConverter,
            $name,
            $value,
            $ehloName,
            $hostName,
            $hostAddress
        );
    }
}
