<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message\IMessagePart;

class ParserMimePartProxy extends ParserPartProxy
{
    protected $endBoundaryFound = false;

    protected $parentBoundaryFound = false;

    protected $mimeBoundary = false;

    protected $allChildrenParsed = false;

    protected $children = [];

    protected $lastAddedChild = null;

    protected function ensureLastChildParsed() : self
    {
        if ($this->lastAddedChild !== null) {
            $this->lastAddedChild->parseAll();
        }
        return $this;
    }

    protected function parseNextChild() : self
    {
        if ($this->allChildrenParsed) {
            return $this;
        }
        $this->parseContent();
        $this->ensureLastChildParsed();
        $next = $this->parser->parseNextChild($this);
        if ($next !== null) {
            $this->children[] = $next;
            $this->lastAddedChild = $next;
        } else {
            $this->allChildrenParsed = true;
        }
        return $this;
    }

    public function popNextChild()
    {
        if (empty($this->children)) {
            $this->parseNextChild();
        }
        $proxy = \array_shift($this->children);
        return ($proxy !== null) ? $proxy->getPart() : null;
    }

    public function parseAll()
    {
        $this->parseContent();
        while (!$this->allChildrenParsed) {
            $this->parseNextChild();
        }
        return $this;
    }

    public function getContentType()
    {
        return $this->getHeaderContainer()->get(HeaderConsts::CONTENT_TYPE);
    }

    public function getMimeBoundary()
    {
        if ($this->mimeBoundary === false) {
            $this->mimeBoundary = null;
            $contentType = $this->getContentType();
            if ($contentType !== null) {
                $this->mimeBoundary = $contentType->getValueFor('boundary');
            }
        }
        return $this->mimeBoundary;
    }

    public function setEndBoundaryFound(string $line)
    {
        $boundary = $this->getMimeBoundary();
        if ($this->getParent() !== null && $this->getParent()->setEndBoundaryFound($line)) {
            $this->parentBoundaryFound = true;
            return true;
        } elseif ($boundary !== null) {
            if ($line === "--$boundary--") {
                $this->endBoundaryFound = true;
                return true;
            } elseif ($line === "--$boundary") {
                return true;
            }
        }
        return false;
    }

    public function isParentBoundaryFound() : bool
    {
        return ($this->parentBoundaryFound);
    }

    public function isEndBoundaryFound() : bool
    {
        return ($this->endBoundaryFound);
    }

    public function setEof() : self
    {
        $this->parentBoundaryFound = true;
        if ($this->getParent() !== null) {
            $this->getParent()->setEof();
        }
        return $this;
    }

    public function setStreamPartAndContentEndPos(int $streamContentEndPos)
    {
        $start = $this->getStreamContentStartPos();
        if ($streamContentEndPos - $start < 0) {
            parent::setStreamPartAndContentEndPos($start);
            $this->setStreamPartEndPos($streamContentEndPos);
        } else {
            parent::setStreamPartAndContentEndPos($streamContentEndPos);
        }
        return $this;
    }

    public function setLastLineEndingLength(int $length)
    {
        $this->getParent()->setLastLineEndingLength($length);
        return $this;
    }

    public function getLastLineEndingLength() : int
    {
        return $this->getParent()->getLastLineEndingLength();
    }
}
