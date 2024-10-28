<?php

namespace ZBateson\MailMimeParser\Header\Part;

use DateTime;
use Exception;
use ZBateson\MbWrapper\MbWrapper;

class DatePart extends LiteralPart
{
    protected $date = null;

    public function __construct(MbWrapper $charsetConverter, string $token)
    {
        $dateToken = \trim($token);
        parent::__construct($charsetConverter, $dateToken);

        if (\preg_match('# [0-9]{4}$#', $dateToken)) {
            $dateToken = \preg_replace('# ([0-9]{4})$#', ' +$1', $dateToken);
        } elseif (\preg_match('#UT$#', $dateToken)) {
            $dateToken = $dateToken . 'C';
        }

        try {
            $this->date = new DateTime($dateToken);
        } catch (Exception $e) {
        }
    }

    public function getDateTime() : ?\DateTime
    {
        return $this->date;
    }
}
