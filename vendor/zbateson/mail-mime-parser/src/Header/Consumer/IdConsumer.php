<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

class IdConsumer extends GenericConsumer
{
    public function getTokenSeparators() : array
    {
        return ['\s+', '<', '>'];
    }

    protected function isEndToken(string $token) : bool
    {
        return ($token === '>');
    }

    protected function isStartToken(string $token) : bool
    {
        return ($token === '<');
    }

    protected function getPartForToken(string $token, bool $isLiteral)
    {
        if (\preg_match('/^\s+$/', $token)) {
            return null;
        }
        return $this->partFactory->newLiteralPart($token);
    }
}
