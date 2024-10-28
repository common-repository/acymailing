<?php

namespace ZBateson\MailMimeParser\Message;

interface IUUEncodedPart extends IMessagePart
{
    public function setFilename(string $filename);

    public function getUnixFileMode() : ?int;

    public function setUnixFileMode(int $mode);
}
