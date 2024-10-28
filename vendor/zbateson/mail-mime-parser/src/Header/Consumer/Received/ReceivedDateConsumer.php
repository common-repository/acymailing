<?php

namespace ZBateson\MailMimeParser\Header\Consumer\Received;

use ZBateson\MailMimeParser\Header\Consumer\DateConsumer;

class ReceivedDateConsumer extends DateConsumer
{
    protected function isStartToken(string $token) : bool
    {
        return ($token === ';');
    }

    protected function getTokenSeparators() : array
    {
        return [';'];
    }
}
