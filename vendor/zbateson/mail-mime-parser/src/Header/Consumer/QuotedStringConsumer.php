<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

class QuotedStringConsumer extends GenericConsumer
{
    public function getSubConsumers() : array
    {
        return [];
    }

    protected function isStartToken(string $token) : bool
    {
        return ($token === '"');
    }

    protected function isEndToken(string $token) : bool
    {
        return ($token === '"');
    }

    protected function getTokenSeparators() : array
    {
        return ['\"'];
    }

    protected function filterIgnoredSpaces(array $parts) : array
    {
        return $parts;
    }

    protected function getPartForToken(string $token, bool $isLiteral)
    {
        return $this->partFactory->newLiteralPart($token);
    }
}
