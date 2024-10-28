<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Parser\PartBuilder;

class ParserNonMimeMessageProxyFactory extends ParserMessageProxyFactory
{
    public function newInstance(PartBuilder $partBuilder, IParser $parser)
    {
        $parserProxy = new ParserNonMimeMessageProxy($partBuilder, $parser);

        $streamContainer = $this->parserPartStreamContainerFactory->newInstance($parserProxy);
        $headerContainer = $this->partHeaderContainerFactory->newInstance($parserProxy->getHeaderContainer());
        $childrenContainer = $this->parserPartChildrenContainerFactory->newInstance($parserProxy);

        $message = new Message(
            $streamContainer,
            $headerContainer,
            $childrenContainer,
            $this->multipartHelper,
            $this->privacyHelper
        );
        $parserProxy->setPart($message);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($message));
        $message->attach($streamContainer);
        return $parserProxy;
    }
}
