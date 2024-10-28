<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

class ParserNonMimeMessageProxy extends ParserMessageProxy
{
    protected $nextPartStart = null;

    protected $nextPartMode = null;

    protected $nextPartFilename = null;

    public function getNextPartStart() : ?int
    {
        return $this->nextPartStart;
    }

    public function getNextPartMode() : ?int
    {
        return $this->nextPartMode;
    }

    public function getNextPartFilename() : ?string
    {
        return $this->nextPartFilename;
    }

    public function setNextPartStart(int $nextPartStart) : self
    {
        $this->nextPartStart = $nextPartStart;
        return $this;
    }

    public function setNextPartMode(int $nextPartMode) : self
    {
        $this->nextPartMode = $nextPartMode;
        return $this;
    }

    public function setNextPartFilename(string $nextPartFilename) : self
    {
        $this->nextPartFilename = $nextPartFilename;
        return $this;
    }

    public function clearNextPart() : self
    {
        $this->nextPartStart = null;
        $this->nextPartMode = null;
        $this->nextPartFilename = null;
        return $this;
    }
}
