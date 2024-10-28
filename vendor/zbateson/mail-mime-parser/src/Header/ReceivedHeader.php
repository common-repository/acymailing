<?php

namespace ZBateson\MailMimeParser\Header;

use DateTime;
use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\CommentPart;
use ZBateson\MailMimeParser\Header\Part\DatePart;

class ReceivedHeader extends ParameterHeader
{
    protected $comments = [];

    protected $date = null;

    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getReceivedConsumer();
    }

    protected function setParseHeaderValue(AbstractConsumer $consumer)
    {
        parent::setParseHeaderValue($consumer);
        foreach ($this->parts as $part) {
            if ($part instanceof CommentPart) {
                $this->comments[] = $part->getComment();
            } elseif ($part instanceof DatePart) {
                $this->date = $part->getDateTime();
            }
        }
        return $this;
    }

    public function getValue() : ?string
    {
        return $this->rawValue;
    }

    public function getFromName()
    {
        return (isset($this->parameters['from'])) ?
            $this->parameters['from']->getEhloName() : null;
    }

    public function getFromHostname()
    {
        return (isset($this->parameters['from'])) ?
            $this->parameters['from']->getHostname() : null;
    }

    public function getFromAddress()
    {
        return (isset($this->parameters['from'])) ?
            $this->parameters['from']->getAddress() : null;
    }

    public function getByName()
    {
        return (isset($this->parameters['by'])) ?
            $this->parameters['by']->getEhloName() : null;
    }

    public function getByHostname()
    {
        return (isset($this->parameters['by'])) ?
            $this->parameters['by']->getHostname() : null;
    }

    public function getByAddress()
    {
        return (isset($this->parameters['by'])) ?
            $this->parameters['by']->getAddress() : null;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getDateTime()
    {
        return $this->date;
    }
}
