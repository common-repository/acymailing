<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use Iterator;

class AddressBaseConsumer extends AbstractConsumer
{
    protected function getSubConsumers() : array
    {
        return [
            $this->consumerService->getAddressConsumer()
        ];
    }

    protected function getTokenSeparators() : array
    {
        return [];
    }

    protected function advanceToNextToken(Iterator $tokens, bool $isStartToken)
    {
        if ($isStartToken) {
            return $this;
        }
        parent::advanceToNextToken($tokens, $isStartToken);
        return $this;
    }

    protected function isEndToken(string $token) : bool
    {
        return false;
    }

    protected function isStartToken(string $token) : bool
    {
        return false;
    }

    protected function getTokenParts(Iterator $tokens) : array
    {
        return $this->getConsumerTokenParts($tokens);
    }

    protected function getPartForToken(string $token, bool $isLiteral)
    {
        return null;
    }
}
