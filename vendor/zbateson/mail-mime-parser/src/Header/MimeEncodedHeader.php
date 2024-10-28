<?php

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPart;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

abstract class MimeEncodedHeader extends AbstractHeader
{
    protected $mimeLiteralPartFactory;

    public function __construct(
        MimeLiteralPartFactory $mimeLiteralPartFactory,
        ConsumerService $consumerService,
        $name,
        $value
    ) {
        $this->mimeLiteralPartFactory = $mimeLiteralPartFactory;
        parent::__construct($consumerService, $name, $value);
    }

    protected function setParseHeaderValue(AbstractConsumer $consumer)
    {
        $value = $this->rawValue;
        $matchp = '~' . MimeLiteralPart::MIME_PART_PATTERN . '~';
        $value = \preg_replace_callback($matchp, function($matches) {
            return $this->mimeLiteralPartFactory->newInstance($matches[0]);
        }, $value);
        $this->parts = $consumer($value);
        return $this;
    }
}
