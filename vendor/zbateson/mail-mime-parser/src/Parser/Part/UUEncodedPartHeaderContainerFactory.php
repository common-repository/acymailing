<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;

class UUEncodedPartHeaderContainerFactory
{
    protected $headerFactory;

    public function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }

    public function newInstance(int $mode, string $filename)
    {
        $container = new UUEncodedPartHeaderContainer($this->headerFactory);
        $container->setUnixFileMode($mode);
        $container->setFilename($filename);
        return $container;
    }
}
