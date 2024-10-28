<?php

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;

class IdHeader extends MimeEncodedHeader
{
    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getIdBaseConsumer();
    }

    public function getId() : ?string
    {
        return $this->getValue();
    }

    public function getIds() : array
    {
        return $this->parts;
    }
}
