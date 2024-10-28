<?php

namespace ZBateson\MailMimeParser\Message\Helper;

use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\IHeader;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message\IMimePart;

class GenericHelper extends AbstractHelper
{
    private static $nonMimeContentFields = ['contentreturn', 'contentidentifier'];

    private function isMimeContentField(IHeader $header, array $exceptions = []) : bool
    {
        return (\stripos($header->getName(), 'Content') === 0
            && !\in_array(\strtolower(\str_replace('-', '', $header->getName())), \array_merge(self::$nonMimeContentFields, $exceptions)));
    }

    public function copyHeader(IMimePart $from, IMimePart $to, $header, $default = null)
    {
        $fromHeader = $from->getHeader($header);
        $set = ($fromHeader !== null) ? $fromHeader->getRawValue() : $default;
        if ($set !== null) {
            $to->setRawHeader($header, $set);
        }
    }

    public function removeContentHeadersAndContent(IMimePart $part) : self
    {
        foreach ($part->getAllHeaders() as $header) {
            if ($this->isMimeContentField($header)) {
                $part->removeHeader($header->getName());
            }
        }
        $part->detachContentStream();
        return $this;
    }

    public function copyContentHeadersAndContent(IMimePart $from, IMimePart $to, $move = false)
    {
        $this->copyHeader($from, $to, HeaderConsts::CONTENT_TYPE, 'text/plain; charset=utf-8');
        if ($from->getHeader(HeaderConsts::CONTENT_TYPE) === null) {
            $this->copyHeader($from, $to, HeaderConsts::CONTENT_TRANSFER_ENCODING, 'quoted-printable');
        } else {
            $this->copyHeader($from, $to, HeaderConsts::CONTENT_TRANSFER_ENCODING);
        }
        foreach ($from->getAllHeaders() as $header) {
            if ($this->isMimeContentField($header, ['contenttype', 'contenttransferencoding'])) {
                $this->copyHeader($from, $to, $header->getName());
            }
        }
        if ($from->hasContent()) {
            $to->attachContentStream($from->getContentStream(), MailMimeParser::DEFAULT_CHARSET);
        }
        if ($move) {
            $this->removeContentHeadersAndContent($from);
        }
    }

    public function createNewContentPartFrom(IMimePart $part)
    {
        $mime = $this->mimePartFactory->newInstance();
        $this->copyContentHeadersAndContent($part, $mime, true);
        return $mime;
    }

    public function movePartContentAndChildren(IMimePart $from, IMimePart $to)
    {
        $this->copyContentHeadersAndContent($from, $to, true);
        if ($from->getChildCount() > 0) {
            foreach ($from->getChildIterator() as $child) {
                $from->removePart($child);
                $to->addChild($child);
            }
        }
    }

    public function replacePart(IMessage $message, IMimePart $part, IMimePart $replacement) : self
    {
        $position = $message->removePart($replacement);
        if ($part === $message) {
            $this->movePartContentAndChildren($replacement, $message);
            return $this;
        }
        $parent = $part->getParent();
        $parent->addChild($replacement, $position);
        $parent->removePart($part);

        return $this;
    }
}
