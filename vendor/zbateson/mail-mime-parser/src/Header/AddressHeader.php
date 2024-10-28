<?php

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\AddressGroupPart;
use ZBateson\MailMimeParser\Header\Part\AddressPart;

class AddressHeader extends AbstractHeader
{
    protected $addresses = [];

    protected $groups = [];

    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getAddressBaseConsumer();
    }

    protected function setParseHeaderValue(AbstractConsumer $consumer)
    {
        parent::setParseHeaderValue($consumer);
        foreach ($this->parts as $part) {
            if ($part instanceof AddressPart) {
                $this->addresses[] = $part;
            } elseif ($part instanceof AddressGroupPart) {
                $this->addresses = \array_merge($this->addresses, $part->getAddresses());
                $this->groups[] = $part;
            }
        }
        return $this;
    }

    public function getAddresses() : array
    {
        return $this->addresses;
    }

    public function getGroups() : array
    {
        return $this->groups;
    }

    public function hasAddress(string $email) : bool
    {
        foreach ($this->addresses as $addr) {
            if (\strcasecmp($addr->getEmail(), $email) === 0) {
                return true;
            }
        }
        return false;
    }

    public function getEmail() : ?string
    {
        return $this->getValue();
    }

    public function getPersonName() : ?string
    {
        if (!empty($this->parts)) {
            return $this->parts[0]->getName();
        }
        return null;
    }
}
