<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;

class ParserPartChildrenContainerFactory
{
    public function newInstance(ParserMimePartProxy $parserProxy)
    {
        return new ParserPartChildrenContainer($parserProxy);
    }
}
