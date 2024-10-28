<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use ArrayObject;
use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPart;
use ZBateson\MailMimeParser\Header\Part\SplitParameterToken;
use ZBateson\MailMimeParser\Header\Part\Token;

class ParameterConsumer extends GenericConsumer
{
    protected function getTokenSeparators() : array
    {
        return [';', '='];
    }

    protected function getTokenSplitPattern() : string
    {
        $sChars = \implode('|', $this->getAllTokenSeparators());
        $mimePartPattern = MimeLiteralPart::MIME_PART_PATTERN_NO_QUOTES;
        return '~(' . $mimePartPattern . '|\\\\.|' . $sChars . ')~';
    }

    protected function getPartForToken(string $token, bool $isLiteral)
    {
        if ($isLiteral) {
            return $this->partFactory->newLiteralPart($token);
        }
        return $this->partFactory->newToken($token);
    }

    private function addToSplitPart(ArrayObject $splitParts, string $name, string $value, int $index, bool $isEncoded)
    {
        $ret = null;
        if (!isset($splitParts[$name])) {
            $ret = $this->partFactory->newSplitParameterToken($name);
            $splitParts[$name] = $ret;
        }
        $splitParts[$name]->addPart($value, $isEncoded, $index);
        return $ret;
    }

    private function getPartFor(string $strName, string $strValue, ArrayObject $splitParts)
    {
        if ($strName === '') {
            return $this->partFactory->newMimeLiteralPart($strValue);
        } elseif (\preg_match('~^\s*([^\*]+)\*(\d*)(\*)?$~', $strName, $matches)) {
            return $this->addToSplitPart(
                $splitParts,
                $matches[1],
                $strValue,
                (int) $matches[2],
                (($matches[2] === '') || !empty($matches[3]))
            );
        }
        return $this->partFactory->newParameterPart($strName, $strValue);
    }

    private function processTokenPart(string $tokenValue, ArrayObject $combined, ArrayObject $splitParts, string &$strName, string &$strCat) : bool
    {
        if ($tokenValue === ';') {
            $combined[] = $this->getPartFor($strName, $strCat, $splitParts);
            $strName = '';
            $strCat = '';
            return true;
        } elseif ($tokenValue === '=' && $strCat !== '') {
            $strName = $strCat;
            $strCat = '';
            return true;
        }
        return false;
    }

    private function finalizeParameterParts(ArrayObject $combined) : array
    {
        foreach ($combined as $key => $part) {
            if ($part instanceof SplitParameterToken) {
                $combined[$key] = $this->partFactory->newParameterPart(
                    $part->getName(),
                    $part->getValue(),
                    $part->getLanguage()
                );
            }
        }
        return $this->filterIgnoredSpaces($combined->getArrayCopy());
    }

    protected function processParts(array $parts) : array
    {
        $combined = new ArrayObject();
        $splitParts = new ArrayObject();
        $strCat = '';
        $strName = '';
        $parts[] = $this->partFactory->newToken(';');
        foreach ($parts as $part) {
            $pValue = $part->getValue();
            if ($part instanceof Token && $this->processTokenPart($pValue, $combined, $splitParts, $strName, $strCat)) {
                continue;
            }
            $strCat .= $pValue;
        }
        return $this->finalizeParameterParts($combined);
    }
}
