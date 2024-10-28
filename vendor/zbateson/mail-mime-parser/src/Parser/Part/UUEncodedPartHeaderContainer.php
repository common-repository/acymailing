<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;

class UUEncodedPartHeaderContainer extends PartHeaderContainer
{
    protected $mode = null;

    protected $filename = null;

    public function getUnixFileMode() : ?int
    {
        return $this->mode;
    }

    public function setUnixFileMode(int $mode)
    {
        $this->mode = $mode;
        return $this;
    }

    public function getFilename() : ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
        return $this;
    }
}
