<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;

class PartHeaderContainerFactory
{
    protected $headerFactory;

    public function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }

    public function newInstance(?PartHeaderContainer $from = null)
    {
        return new PartHeaderContainer($this->headerFactory, $from);
    }
}
