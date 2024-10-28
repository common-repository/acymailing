<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\CommentPart;

class IdBaseConsumer extends AbstractConsumer
{
    protected function getSubConsumers() : array
    {
        return [
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getQuotedStringConsumer(),
            $this->consumerService->getIdConsumer()
        ];
    }

    protected function getTokenSeparators() : array
    {
        return ['\s+'];
    }

    protected function isEndToken(string $token) : bool
    {
        return false;
    }

    protected function isStartToken(string $token) : bool
    {
        return false;
    }

    protected function getPartForToken(string $token, bool $isLiteral)
    {
        if (\preg_match('/^\s+$/', $token)) {
            return null;
        }
        return $this->partFactory->newLiteralPart($token);
    }

    protected function processParts(array $parts) : array
    {
        return \array_values(\array_filter($parts, function($part) {
            return !(empty($part) || $part instanceof CommentPart);
        }));
    }
}
