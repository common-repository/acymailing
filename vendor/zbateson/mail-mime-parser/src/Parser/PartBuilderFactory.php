<?php

namespace ZBateson\MailMimeParser\Parser;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;

class PartBuilderFactory
{
    public function newPartBuilder(PartHeaderContainer $headerContainer, StreamInterface $messageStream)
    {
        return new PartBuilder($headerContainer, $messageStream);
    }

    public function newChildPartBuilder(PartHeaderContainer $headerContainer, ParserPartProxy $parent)
    {
        return new PartBuilder(
            $headerContainer,
            null,
            $parent
        );
    }
}
