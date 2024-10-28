<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use Iterator;
use ZBateson\MailMimeParser\Header\Part\Token;

class SubjectConsumer extends GenericConsumer
{
    protected function getSubConsumers() : array
    {
        return [];
    }

    protected function getPartForToken(string $token, bool $isLiteral)
    {
        if ($isLiteral) {
            return $this->partFactory->newLiteralPart($token);
        } elseif (\preg_match('/^\s+$/', $token)) {
            if (\preg_match('/^[\r\n]/', $token)) {
                return $this->partFactory->newToken(' ');
            }
            return $this->partFactory->newToken($token);
        }
        return $this->partFactory->newInstance($token);
    }

    protected function getTokenParts(Iterator $tokens) : array
    {
        return $this->getConsumerTokenParts($tokens);
    }

    protected function getTokenSplitPattern() : string
    {
        $sChars = \implode('|', $this->getAllTokenSeparators());
        return '~(' . $sChars . ')~';
    }
}
