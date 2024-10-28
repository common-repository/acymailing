<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use Iterator;
use ZBateson\MailMimeParser\Header\Part\Token;

class ReceivedConsumer extends AbstractConsumer
{
    protected function getTokenSeparators() : array
    {
        return [];
    }

    protected function isEndToken(string $token) : bool
    {
        return false;
    }

    protected function isStartToken(string $token) : bool
    {
        return false;
    }

    protected function getSubConsumers() : array
    {
        return [
            $this->consumerService->getSubReceivedConsumer('from'),
            $this->consumerService->getSubReceivedConsumer('by'),
            $this->consumerService->getSubReceivedConsumer('via'),
            $this->consumerService->getSubReceivedConsumer('with'),
            $this->consumerService->getSubReceivedConsumer('id'),
            $this->consumerService->getSubReceivedConsumer('for'),
            $this->consumerService->getSubReceivedConsumer('date'),
            $this->consumerService->getCommentConsumer()
        ];
    }

    protected function getTokenSplitPattern() : string
    {
        $sChars = \implode('|', $this->getAllTokenSeparators());
        return '~(' . $sChars . ')~';
    }

    protected function advanceToNextToken(Iterator $tokens, bool $isStartToken)
    {
        if ($isStartToken) {
            $tokens->next();
        } elseif ($tokens->valid() && !$this->isEndToken($tokens->current())) {
            foreach ($this->getSubConsumers() as $consumer) {
                if ($consumer->isStartToken($tokens->current())) {
                    return $this;
                }
            }
            $tokens->next();
        }
        return $this;
    }

    protected function processParts(array $parts) : array
    {
        $ret = [];
        foreach ($parts as $part) {
            if ($part instanceof Token) {
                continue;
            }
            $ret[] = $part;
        }
        return $ret;
    }
}
