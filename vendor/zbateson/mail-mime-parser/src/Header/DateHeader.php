<?php

namespace ZBateson\MailMimeParser\Header;

use DateTimeImmutable;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\DatePart;

class DateHeader extends AbstractHeader
{
    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getDateConsumer();
    }

    public function getDateTime() : ?\DateTime
    {
        if (!empty($this->parts) && $this->parts[0] instanceof DatePart) {
            return $this->parts[0]->getDateTime();
        }
        return null;
    }

    public function getDateTimeImmutable() : ?\DateTimeImmutable
    {
        $dateTime = $this->getDateTime();
        if ($dateTime !== null) {
            return DateTimeImmutable::createFromMutable($dateTime);
        }
        return null;
    }
}
