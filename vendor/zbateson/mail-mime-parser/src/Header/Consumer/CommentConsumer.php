<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use Iterator;
use ZBateson\MailMimeParser\Header\Part\CommentPart;
use ZBateson\MailMimeParser\Header\Part\LiteralPart;

class CommentConsumer extends GenericConsumer
{
    protected function getTokenSeparators() : array
    {
        return ['\(', '\)'];
    }

    protected function isStartToken(string $token) : bool
    {
        return ($token === '(');
    }

    protected function isEndToken(string $token) : bool
    {
        return ($token === ')');
    }

    protected function getPartForToken(string $token, bool $isLiteral)
    {
        return $this->partFactory->newToken($token);
    }

    protected function advanceToNextToken(Iterator $tokens, bool $isStartToken)
    {
        $tokens->next();
        return $this;
    }

    protected function processParts(array $parts) : array
    {
        $comment = '';
        foreach ($parts as $part) {
            if ($part instanceof CommentPart) {
                $comment .= '(' . $part->getComment() . ')';
            } elseif ($part instanceof LiteralPart) {
                $comment .= '"' . \str_replace('(["\\])', '\$1', $part->getValue()) . '"';
            } else {
                $comment .= $part->getValue();
            }
        }
        return [$this->partFactory->newCommentPart($comment)];
    }
}
