<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

class SplitParameterToken extends HeaderPart
{
    protected $name;

    protected $encodedParts = [];

    protected $literalParts = [];

    protected $language;

    protected $charset = 'ISO-8859-1';

    public function __construct(MbWrapper $charsetConverter, $name)
    {
        parent::__construct($charsetConverter);
        $this->name = \trim($name);
    }

    protected function extractMetaInformationAndValue(string $value, int $index) : self
    {
        if (\preg_match('~^([^\']*)\'([^\']*)\'(.*)$~', $value, $matches)) {
            if ($index === 0) {
                $this->charset = (!empty($matches[1])) ? $matches[1] : $this->charset;
                $this->language = (!empty($matches[2])) ? $matches[2] : $this->language;
            }
            $value = $matches[3];
        }
        $this->encodedParts[$index] = $value;
        return $this;
    }

    public function addPart($value, $isEncoded, $index)
    {
        if (empty($index)) {
            $index = 0;
        }
        if ($isEncoded) {
            $this->extractMetaInformationAndValue($value, $index);
        } else {
            $this->literalParts[$index] = $this->convertEncoding($value);
        }
    }

    private function getNextEncodedValue() : string
    {
        $cur = \current($this->encodedParts);
        $key = \key($this->encodedParts);
        $running = '';
        while ($cur !== false) {
            $running .= $cur;
            $cur = \next($this->encodedParts);
            $nKey = \key($this->encodedParts);
            if ($nKey !== $key + 1) {
                break;
            }
            $key = $nKey;
        }
        return $this->convertEncoding(
            \rawurldecode($running),
            $this->charset,
            true
        );
    }

    public function getValue() : ?string
    {
        $parts = $this->literalParts;

        \reset($this->encodedParts);
        \ksort($this->encodedParts);
        while (\current($this->encodedParts) !== false) {
            $parts[\key($this->encodedParts)] = $this->getNextEncodedValue();
        }

        \ksort($parts);
        return \array_reduce(
            $parts,
            function($carry, $item) {
                return $carry . $item;
            },
            ''
        );
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLanguage()
    {
        return $this->language;
    }
}
