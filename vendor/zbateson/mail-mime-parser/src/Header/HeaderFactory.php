<?php

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

class HeaderFactory
{
    protected $consumerService;

    protected $mimeLiteralPartFactory;

    protected $types = [
        \ZBateson\MailMimeParser\Header\AddressHeader::class => [
            'from',
            'to',
            'cc',
            'bcc',
            'sender',
            'replyto',
            'resentfrom',
            'resentto',
            'resentcc',
            'resentbcc',
            'resentreplyto',
            'returnpath',
            'deliveredto',
        ],
        \ZBateson\MailMimeParser\Header\DateHeader::class => [
            'date',
            'resentdate',
            'deliverydate',
            'expires',
            'expirydate',
            'replyby',
        ],
        \ZBateson\MailMimeParser\Header\ParameterHeader::class => [
            'contenttype',
            'contentdisposition',
            'receivedspf',
            'authenticationresults',
            'dkimsignature',
            'autocrypt',
        ],
        \ZBateson\MailMimeParser\Header\SubjectHeader::class => [
            'subject',
        ],
        \ZBateson\MailMimeParser\Header\IdHeader::class => [
            'messageid',
            'contentid',
            'inreplyto',
            'references'
        ],
        \ZBateson\MailMimeParser\Header\ReceivedHeader::class => [
            'received'
        ]
    ];

    protected $genericType = \ZBateson\MailMimeParser\Header\GenericHeader::class;

    public function __construct(ConsumerService $consumerService, MimeLiteralPartFactory $mimeLiteralPartFactory)
    {
        $this->consumerService = $consumerService;
        $this->mimeLiteralPartFactory = $mimeLiteralPartFactory;
    }

    public function getNormalizedHeaderName(string $header) : string
    {
        return \preg_replace('/[^a-z0-9]/', '', \strtolower($header));
    }

    private function getClassFor(string $name) : string
    {
        $test = $this->getNormalizedHeaderName($name);
        foreach ($this->types as $class => $matchers) {
            foreach ($matchers as $matcher) {
                if ($test === $matcher) {
                    return $class;
                }
            }
        }
        return $this->genericType;
    }

    public function newInstance(string $name, string $value)
    {
        $class = $this->getClassFor($name);
        return $this->newInstanceOf($name, $value, $class);
    }

    public function newInstanceOf(string $name, string $value, string $iHeaderClass) : IHeader
    {
        if (\is_a($iHeaderClass, 'ZBateson\MailMimeParser\Header\MimeEncodedHeader', true)) {
            return new $iHeaderClass(
                $this->mimeLiteralPartFactory,
                $this->consumerService,
                $name,
                $value
            );
        }
        return new $iHeaderClass($this->consumerService, $name, $value);
    }
}
