<?php


namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Message\Helper\MultipartHelper;
use ZBateson\MailMimeParser\Message\Helper\PrivacyHelper;
use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainerFactory;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Stream\StreamFactory;

class ParserMessageProxyFactory extends ParserMimePartProxyFactory
{
    protected $multipartHelper;

    protected $privacyHelper;

    public function __construct(
        StreamFactory $sdf,
        PartHeaderContainerFactory $phcf,
        ParserPartStreamContainerFactory $pscf,
        ParserPartChildrenContainerFactory $ppccf,
        MultipartHelper $multipartHelper,
        PrivacyHelper $privacyHelper
    ) {
        parent::__construct($sdf, $phcf, $pscf, $ppccf);
        $this->multipartHelper = $multipartHelper;
        $this->privacyHelper = $privacyHelper;
    }

    public function newInstance(PartBuilder $partBuilder, IParser $parser)
    {
        $parserProxy = new ParserMessageProxy($partBuilder, $parser);

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
