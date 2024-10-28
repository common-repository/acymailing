<?php

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;

class GenericHeader extends AbstractHeader
{
    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getGenericConsumer();
    }
}
