<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

class ParameterPart extends MimeLiteralPart
{
    protected $name;

    protected $language;

    public function __construct(MbWrapper $charsetConverter, $name, $value, $language = null)
    {
        if ($language !== null) {
            parent::__construct($charsetConverter, '');
            $this->name = $name;
            $this->value = $value;
            $this->language = $language;
        } else {
            parent::__construct($charsetConverter, \trim($value));
            $this->name = $this->decodeMime(\trim($name));
        }
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
