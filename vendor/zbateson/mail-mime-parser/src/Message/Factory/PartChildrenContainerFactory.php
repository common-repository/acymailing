<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use ZBateson\MailMimeParser\Message\PartChildrenContainer;

class PartChildrenContainerFactory
{
    public function newInstance()
    {
        return new PartChildrenContainer();
    }
}
