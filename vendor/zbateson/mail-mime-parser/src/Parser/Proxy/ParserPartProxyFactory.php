<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Parser\PartBuilder;

abstract class ParserPartProxyFactory
{
    abstract public function newInstance(PartBuilder $partBuilder, IParser $parser);
}
