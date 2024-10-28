<?php

namespace ZBateson\MailMimeParser\Message;

interface IMultiPart extends IMessagePart
{
    public function getPart($index, $fnFilter = null);

    public function getAllParts($fnFilter = null);

    public function getPartCount($fnFilter = null);

    public function getChild($index, $fnFilter = null);

    public function getChildParts($fnFilter = null);

    public function getChildCount($fnFilter = null);

    public function getChildIterator();

    public function getPartByMimeType($mimeType, $index = 0);

    public function getAllPartsByMimeType($mimeType);

    public function getCountOfPartsByMimeType($mimeType);

    public function getPartByContentId($contentId);

    public function addChild(IMessagePart $part, ?int $position = null);

    public function removePart(IMessagePart $part) : ?int;

    public function removeAllParts($fnFilter = null) : int;
}
