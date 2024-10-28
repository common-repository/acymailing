<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\AddressGroupPart;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\Header\Part\CommentPart;
use ZBateson\MailMimeParser\Header\Part\LiteralPart;

class AddressConsumer extends AbstractConsumer
{
    protected function getSubConsumers() : array
    {
        return [
            $this->consumerService->getAddressGroupConsumer(),
            $this->consumerService->getAddressEmailConsumer(),
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getQuotedStringConsumer(),
        ];
    }

    public function getTokenSeparators() : array
    {
        return [',', ';', '\s+'];
    }

    protected function isEndToken(string $token) : bool
    {
        return ($token === ',' || $token === ';');
    }

    protected function isStartToken(string $token) : bool
    {
        return true;
    }

    protected function processParts(array $parts) : array
    {
        $strName = '';
        $strEmail = '';
        foreach ($parts as $part) {
            if ($part instanceof AddressGroupPart) {
                return [
                    $this->partFactory->newAddressGroupPart(
                        $part->getAddresses(),
                        $strName
                    )
                ];
            } elseif ($part instanceof AddressPart) {
                return [$this->partFactory->newAddressPart($strName, $part->getEmail())];
            } elseif ((($part instanceof LiteralPart) && !($part instanceof CommentPart)) && $part->getValue() !== '') {
                $strEmail .= '"' . \preg_replace('/(["\\\])/', '\\\$1', $part->getValue()) . '"';
            } else {
                $strEmail .= \preg_replace('/\s+/', '', $part->getValue());
            }
            $strName .= $part->getValue();
        }
        return [$this->partFactory->newAddressPart('', $strEmail)];
    }
}
