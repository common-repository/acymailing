<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\AddressGroupPart;

class AddressGroupConsumer extends AddressBaseConsumer
{
    public function getTokenSeparators() : array
    {
        return [':', ';'];
    }

    protected function isEndToken(string $token) : bool
    {
        return ($token === ';');
    }

    protected function isStartToken(string $token) : bool
    {
        return ($token === ':');
    }

    protected function processParts(array $parts) : array
    {
        $emails = [];
        foreach ($parts as $part) {
            if ($part instanceof AddressGroupPart) {
                $emails = \array_merge($emails, $part->getAddresses());
                continue;
            }
            $emails[] = $part;
        }
        $group = $this->partFactory->newAddressGroupPart($emails);
        return [$group];
    }
}
