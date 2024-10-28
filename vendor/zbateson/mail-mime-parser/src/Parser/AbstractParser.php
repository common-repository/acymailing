<?php

namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxyFactory;

abstract class AbstractParser implements IParser
{
    protected $parserMessageProxyFactory;

    protected $parserPartProxyFactory;

    protected $partBuilderFactory;

    protected $parserManager;

    public function __construct(
        ParserPartProxyFactory $parserMessageProxyFactory,
        ParserPartProxyFactory $parserPartProxyFactory,
        PartBuilderFactory $partBuilderFactory
    ) {
        $this->parserMessageProxyFactory = $parserMessageProxyFactory;
        $this->parserPartProxyFactory = $parserPartProxyFactory;
        $this->partBuilderFactory = $partBuilderFactory;
    }

    public function setParserManager(ParserManager $pm)
    {
        $this->parserManager = $pm;
        return $this;
    }

    public function getParserMessageProxyFactory()
    {
        return $this->parserMessageProxyFactory;
    }

    public function getParserPartProxyFactory()
    {
        return $this->parserPartProxyFactory;
    }
}
