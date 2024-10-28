<?php

namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;

class ParserManager
{
    protected $parsers = [];

    public function __construct(MimeParser $mimeParser, NonMimeParser $nonMimeParser)
    {
        $this->setParsers([$mimeParser, $nonMimeParser]);
    }

    public function setParsers(array $parsers) : self
    {
        foreach ($parsers as $parser) {
            $parser->setParserManager($this);
        }
        $this->parsers = $parsers;
        return $this;
    }

    public function prependParser(IParser $parser) : self
    {
        $parser->setParserManager($this);
        \array_unshift($this->parsers, $parser);
        return $this;
    }

    public function createParserProxyFor(PartBuilder $partBuilder)
    {
        foreach ($this->parsers as $parser) {
            if ($parser->canParse($partBuilder)) {
                $factory = ($partBuilder->getParent() === null) ?
                    $parser->getParserMessageProxyFactory() :
                    $parser->getParserPartProxyFactory();
                return $factory->newInstance($partBuilder, $parser);
            }
        }
        return null;
    }
}
