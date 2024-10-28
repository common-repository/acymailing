<?php

namespace ZBateson\MailMimeParser\Parser;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;

class MessageParser
{
    protected $partHeaderContainerFactory;

    protected $parserManager;

    protected $partBuilderFactory;

    protected $headerParser;

    public function __construct(
        PartBuilderFactory $pbf,
        PartHeaderContainerFactory $phcf,
        ParserManager $pm,
        HeaderParser $hp
    ) {
        $this->partBuilderFactory = $pbf;
        $this->partHeaderContainerFactory = $phcf;
        $this->parserManager = $pm;
        $this->headerParser = $hp;
    }

    public static function readLine($handle)
    {
        $size = 4096;
        $ret = $line = \fgets($handle, $size);
        while (\strlen($line) === $size - 1 && \substr($line, -1) !== "\n") {
            $line = \fgets($handle, $size);
        }
        return $ret;
    }

    public function parse(StreamInterface $stream)
    {
        $headerContainer = $this->partHeaderContainerFactory->newInstance();
        $partBuilder = $this->partBuilderFactory->newPartBuilder($headerContainer, $stream);
        $this->headerParser->parse(
            $partBuilder->getMessageResourceHandle(),
            $headerContainer
        );
        $proxy = $this->parserManager->createParserProxyFor($partBuilder);
        return $proxy->getPart();
    }
}
