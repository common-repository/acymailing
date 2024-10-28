<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

class DateConsumer extends GenericConsumer
{
    protected function getPartForToken(string $token, bool $isLiteral)
    {
        return $this->partFactory->newLiteralPart($token);
    }

    protected function processParts(array $parts) : array
    {
        $strValue = '';
        foreach ($parts as $part) {
            $strValue .= $part->getValue();
        }
        return [$this->partFactory->newDatePart($strValue)];
    }
}
