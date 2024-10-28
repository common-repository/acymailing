<?php

namespace ZBateson\MailMimeParser\Header;

interface IHeaderPart
{
    public function getValue() : ?string;

    public function __toString() : string;
}
