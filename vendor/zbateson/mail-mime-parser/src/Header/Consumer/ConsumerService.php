<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumer;
use ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumer;
use ZBateson\MailMimeParser\Header\Consumer\Received\ReceivedDateConsumer;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

class ConsumerService
{
    protected $partFactory;

    protected $mimeLiteralPartFactory;

    protected $receivedConsumers = [
        'from' => null,
        'by' => null,
        'via' => null,
        'with' => null,
        'id' => null,
        'for' => null,
        'date' => null
    ];

    public function __construct(HeaderPartFactory $partFactory, MimeLiteralPartFactory $mimeLiteralPartFactory)
    {
        $this->partFactory = $partFactory;
        $this->mimeLiteralPartFactory = $mimeLiteralPartFactory;
    }

    public function getAddressBaseConsumer()
    {
        return AddressBaseConsumer::getInstance($this, $this->partFactory);
    }

    public function getAddressConsumer()
    {
        return AddressConsumer::getInstance($this, $this->partFactory);
    }

    public function getAddressGroupConsumer()
    {
        return AddressGroupConsumer::getInstance($this, $this->partFactory);
    }

    public function getAddressEmailConsumer()
    {
        return AddressEmailConsumer::getInstance($this, $this->partFactory);
    }

    public function getCommentConsumer()
    {
        return CommentConsumer::getInstance($this, $this->partFactory);
    }

    public function getGenericConsumer()
    {
        return GenericConsumer::getInstance($this, $this->mimeLiteralPartFactory);
    }

    public function getSubjectConsumer()
    {
        return SubjectConsumer::getInstance($this, $this->mimeLiteralPartFactory);
    }

    public function getQuotedStringConsumer()
    {
        return QuotedStringConsumer::getInstance($this, $this->partFactory);
    }

    public function getDateConsumer()
    {
        return DateConsumer::getInstance($this, $this->partFactory);
    }

    public function getParameterConsumer()
    {
        return ParameterConsumer::getInstance($this, $this->partFactory);
    }

    public function getSubReceivedConsumer(string $partName)
    {
        if (empty($this->receivedConsumers[$partName])) {
            $consumer = null;
            if ($partName === 'from' || $partName === 'by') {
                $consumer = new DomainConsumer($this, $this->partFactory, $partName);
            } elseif ($partName === 'date') {
                $consumer = new ReceivedDateConsumer($this, $this->partFactory);
            } else {
                $consumer = new GenericReceivedConsumer($this, $this->partFactory, $partName);
            }
            $this->receivedConsumers[$partName] = $consumer;
        }
        return $this->receivedConsumers[$partName];
    }

    public function getReceivedConsumer()
    {
        return ReceivedConsumer::getInstance($this, $this->partFactory);
    }

    public function getIdConsumer()
    {
        return IdConsumer::getInstance($this, $this->partFactory);
    }

    public function getIdBaseConsumer()
    {
        return IdBaseConsumer::getInstance($this, $this->partFactory);
    }
}
