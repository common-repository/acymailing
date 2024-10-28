<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\CommentPart;
use ZBateson\MailMimeParser\Header\Part\LiteralPart;

class AddressEmailConsumer extends AbstractConsumer
{
    protected function getSubConsumers() : array
    {
        return [
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getQuotedStringConsumer(),
        ];
    }

    public function getTokenSeparators() : array
    {
        return ['<', '>'];
    }

    protected function isEndToken(string $token) : bool
    {
        return ($token === '>');
    }

    protected function isStartToken(string $token) : bool
    {
        return ($token === '<');
    }

    protected function processParts(array $parts) : array
    {
        $strEmail = '';
        foreach ($parts as $p) {
            $val = $p->getValue();
            if ((($p instanceof LiteralPart) && !($p instanceof CommentPart)) && $val !== '') {
                $val = '"' . \preg_replace('/(["\\\])/', '\\\$1', $val) . '"';
            } else {
                $val = \preg_replace('/\s+/', '', $val);
            }
            $strEmail .= $val;
        }
        return [$this->partFactory->newAddressPart('', $strEmail)];
    }
}
