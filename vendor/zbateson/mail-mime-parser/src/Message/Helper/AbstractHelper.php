<?php

namespace ZBateson\MailMimeParser\Message\Helper;

use ZBateson\MailMimeParser\Message\Factory\IMimePartFactory;
use ZBateson\MailMimeParser\Message\Factory\IUUEncodedPartFactory;

abstract class AbstractHelper
{
    protected $mimePartFactory;

    protected $uuEncodedPartFactory;

    public function __construct(
        IMimePartFactory $mimePartFactory,
        IUUEncodedPartFactory $uuEncodedPartFactory
    ) {
        $this->mimePartFactory = $mimePartFactory;
        $this->uuEncodedPartFactory = $uuEncodedPartFactory;
    }
}
