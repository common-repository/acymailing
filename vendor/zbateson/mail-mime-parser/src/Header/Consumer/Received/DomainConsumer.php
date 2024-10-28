<?php

namespace ZBateson\MailMimeParser\Header\Consumer\Received;

use ZBateson\MailMimeParser\Header\Part\CommentPart;

class DomainConsumer extends GenericReceivedConsumer
{
    protected function isEndToken(string $token) : bool
    {
        if ($token === ')') {
            return true;
        }
        return parent::isEndToken($token);
    }

    private function matchHostPart(string $value, ?string &$hostname, ?string &$address) : bool
    {
        $matches = [];
        $pattern = '~^(\[(IPv[64])?(?P<addr1>[a-f\d\.\:]+)\])?\s*(helo=)?(?P<name>[a-z0-9\-]+[a-z0-9\-\.]+)?\s*(\[(IPv[64])?(?P<addr2>[a-f\d\.\:]+)\])?$~i';
        if (\preg_match($pattern, $value, $matches)) {
            if (!empty($matches['name'])) {
                $hostname = $matches['name'];
            }
            if (!empty($matches['addr1'])) {
                $address = $matches['addr1'];
            }
            if (!empty($matches['addr2'])) {
                $address = $matches['addr2'];
            }
            return true;
        }
        return false;
    }

    protected function processParts(array $parts) : array
    {
        $ehloName = null;
        $hostname = null;
        $address = null;
        $commentPart = null;

        $filtered = $this->filterIgnoredSpaces($parts);
        foreach ($filtered as $part) {
            if ($part instanceof CommentPart) {
                $commentPart = $part;
                continue;
            }
            $ehloName .= $part->getValue();
        }

        $strValue = $ehloName;
        if ($commentPart !== null && $this->matchHostPart($commentPart->getComment(), $hostname, $address)) {
            $strValue .= ' (' . $commentPart->getComment() . ')';
            $commentPart = null;
        }

        $domainPart = $this->partFactory->newReceivedDomainPart(
            $this->getPartName(),
            $strValue,
            $ehloName,
            $hostname,
            $address
        );
        return \array_filter([$domainPart, $commentPart]);
    }
}
