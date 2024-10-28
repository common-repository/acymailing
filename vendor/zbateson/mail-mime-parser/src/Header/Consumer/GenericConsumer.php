<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\HeaderPart;
use ZBateson\MailMimeParser\Header\Part\Token;

class GenericConsumer extends AbstractConsumer
{
    protected function getSubConsumers() : array
    {
        return [
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getQuotedStringConsumer(),
        ];
    }

    protected function getTokenSeparators() : array
    {
        return ['\s+'];
    }

    protected function isEndToken(string $token) : bool
    {
        return false;
    }

    protected function isStartToken(string $token) : bool
    {
        return false;
    }

    private function shouldAddSpace(HeaderPart $nextPart, HeaderPart $lastPart) : bool
    {
        return (!$lastPart->ignoreSpacesAfter() || !$nextPart->ignoreSpacesBefore());
    }

    private function addSpaceToRetParts(array $parts, array &$retParts, int $curIndex, HeaderPart &$spacePart, HeaderPart $lastPart) : self
    {
        $nextPart = $parts[$curIndex];
        if ($this->shouldAddSpace($nextPart, $lastPart)) {
            $retParts[] = $spacePart;
            $spacePart = null;
        }
        return $this;
    }

    private function addSpaces(array $parts, array &$retParts, int $curIndex, ?HeaderPart &$spacePart = null) : self
    {
        $lastPart = \end($retParts);
        if ($spacePart !== null && $curIndex < \count($parts) && $parts[$curIndex]->getValue() !== '' && $lastPart !== false) {
            $this->addSpaceToRetParts($parts, $retParts, $curIndex, $spacePart, $lastPart);
        }
        return $this;
    }

    private function isSpaceToken(HeaderPart $part) : bool
    {
        return ($part instanceof Token && $part->isSpace());
    }

    protected function filterIgnoredSpaces(array $parts)
    {
        $partsFiltered = \array_values(\array_filter($parts));
        $retParts = [];
        $spacePart = null;
        $count = \count($partsFiltered);
        for ($i = 0; $i < $count; ++$i) {
            $part = $partsFiltered[$i];
            if ($this->isSpaceToken($part)) {
                $spacePart = $part;
                continue;
            }
            $this->addSpaces($partsFiltered, $retParts, $i, $spacePart);
            $retParts[] = $part;
        }
        return $retParts;
    }

    protected function processParts(array $parts) : array
    {
        $strValue = '';
        $filtered = $this->filterIgnoredSpaces($parts);
        foreach ($filtered as $part) {
            $strValue .= $part->getValue();
        }
        return [$this->partFactory->newLiteralPart($strValue)];
    }
}
