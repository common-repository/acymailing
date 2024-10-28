<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

class ParserMessageProxy extends ParserMimePartProxy
{
    protected $lastLineEndingLength = 0;

    public function getLastLineEndingLength() : int
    {
        return $this->lastLineEndingLength;
    }

    public function setLastLineEndingLength(int $lastLineEndingLength)
    {
        $this->lastLineEndingLength = $lastLineEndingLength;
        return $this;
    }
}
