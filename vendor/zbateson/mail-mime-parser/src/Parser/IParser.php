<?php

namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxyFactory;

interface IParser
{
    public function setParserManager(ParserManager $pm);

    public function canParse(PartBuilder $part) : bool;

    public function getParserMessageProxyFactory();

    public function getParserPartProxyFactory();

    public function parseContent(ParserPartProxy $proxy);

    public function parseNextChild(ParserMimePartProxy $proxy);
}
