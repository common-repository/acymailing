<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

class ParserUUEncodedPartProxy extends ParserPartProxy
{
    public function getNextPartStart() : ?int
    {
        return $this->getParent()->getNextPartStart();
    }

    public function getNextPartMode() : ?int
    {
        return $this->getParent()->getNextPartMode();
    }

    public function getNextPartFilename() : ?string
    {
        return $this->getParent()->getNextPartFilename();
    }

    public function setNextPartStart(int $nextPartStart) : self
    {
        $this->getParent()->setNextPartStart($nextPartStart);
        return $this;
    }

    public function setNextPartMode(int $nextPartMode) : self
    {
        $this->getParent()->setNextPartMode($nextPartMode);
        return $this;
    }

    public function setNextPartFilename(string $nextPartFilename) : self
    {
        $this->getParent()->setNextPartFilename($nextPartFilename);
        return $this;
    }

    public function getUnixFileMode() : ?int
    {
        return $this->getHeaderContainer()->getUnixFileMode();
    }

    public function getFilename() : ?string
    {
        return $this->getHeaderContainer()->getFilename();
    }
}
