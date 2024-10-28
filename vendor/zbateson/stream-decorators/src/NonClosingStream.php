<?php

namespace ZBateson\StreamDecorators;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

class NonClosingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private $stream;

    public function close() : void
    {
        $this->stream = null;
    }

    public function detach()
    {
        $this->stream = null;

        return null;
    }
}
