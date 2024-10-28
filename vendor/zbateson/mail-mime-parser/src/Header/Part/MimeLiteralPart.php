<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

class MimeLiteralPart extends LiteralPart
{
    public const MIME_PART_PATTERN = '=\?[^?=]+\?[QBqb]\?[^\?]+\?=';

    public const MIME_PART_PATTERN_NO_QUOTES = '=\?[^\?=]+\?[QBqb]\?[^\?"]+\?=';

    protected $canIgnoreSpacesBefore = false;

    protected $canIgnoreSpacesAfter = false;

    protected $languages = [];

    public function __construct(MbWrapper $charsetConverter, $token)
    {
        parent::__construct($charsetConverter);
        $this->value = $this->decodeMime($token);
        $pattern = self::MIME_PART_PATTERN;
        $this->canIgnoreSpacesBefore = (bool) \preg_match("/^\s*{$pattern}/", $token);
        $this->canIgnoreSpacesAfter = (bool) \preg_match("/{$pattern}\s*\$/", $token);
    }

    protected function decodeMime(string $value) : string
    {
        $pattern = self::MIME_PART_PATTERN;
        $value = \preg_replace("/($pattern)\\s+(?=$pattern)/", '$1', $value);
        $aMimeParts = \preg_split("/($pattern)/", $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $ret = '';
        foreach ($aMimeParts as $entity) {
            $ret .= $this->decodeSplitPart($entity);
        }
        return $ret;
    }

    private function decodeMatchedEntity(array $matches) : string
    {
        $body = $matches[4];
        if (\strtoupper($matches[3]) === 'Q') {
            $body = \quoted_printable_decode(\str_replace('_', '=20', $body));
        } else {
            $body = \base64_decode($body);
        }
        $language = $matches[2];
        $decoded = $this->convertEncoding($body, $matches[1], true);
        $this->addToLanguage($decoded, $language);
        return $decoded;
    }

    private function decodeSplitPart(string $entity) : string
    {
        if (\preg_match('/^=\?([A-Za-z\-_0-9]+)\*?([A-Za-z\-_0-9]+)?\?([QBqb])\?([^\?]*)\?=$/', $entity, $matches)) {
            return $this->decodeMatchedEntity($matches);
        }
        $decoded = $this->convertEncoding($entity);
        $this->addToLanguage($decoded);
        return $decoded;
    }

    public function ignoreSpacesBefore() : bool
    {
        return $this->canIgnoreSpacesBefore;
    }

    public function ignoreSpacesAfter() : bool
    {
        return $this->canIgnoreSpacesAfter;
    }

    protected function addToLanguage(string $part, ?string $language = null) : self
    {
        $this->languages[] = [
            'lang' => $language,
            'value' => $part
        ];
        return $this;
    }

    public function getLanguageArray()
    {
        return $this->languages;
    }
}
