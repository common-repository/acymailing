<?php

namespace ZBateson\MailMimeParser\Header;

interface IHeader
{
    public function getParts() : array;

    public function getValue() : ?string;

    public function getRawValue() : string;

    public function getName() : string;

    public function __toString() : string;
}
