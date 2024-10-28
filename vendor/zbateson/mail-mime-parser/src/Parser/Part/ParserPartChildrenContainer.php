<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\PartChildrenContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;

class ParserPartChildrenContainer extends PartChildrenContainer
{
    protected $parserProxy;

    private $allParsed = false;

    public function __construct(ParserMimePartProxy $parserProxy)
    {
        parent::__construct([]);
        $this->parserProxy = $parserProxy;
    }

    public function offsetExists($offset) : bool
    {
        $exists = parent::offsetExists($offset);
        while (!$exists && !$this->allParsed) {
            $child = $this->parserProxy->popNextChild();
            if ($child === null) {
                $this->allParsed = true;
            } else {
                $this->add($child);
            }
            $exists = parent::offsetExists($offset);
        }
        return $exists;
    }
}
