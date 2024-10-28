<?php

namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;

class HeaderParser
{
    private function addRawHeaderToPart(string $header, PartHeaderContainer $headerContainer) : self
    {
        if ($header !== '' && \strpos($header, ':') !== false) {
            $a = \explode(':', $header, 2);
            $headerContainer->add($a[0], \trim($a[1]));
        }
        return $this;
    }

    public function parse($handle, PartHeaderContainer $container) : self
    {
        $header = '';
        do {
            $line = MessageParser::readLine($handle);
            if ($line === false || $line === '' || $line[0] !== "\t" && $line[0] !== ' ') {
                $this->addRawHeaderToPart($header, $container);
                $header = '';
            } else {
                $line = "\r\n" . $line;
            }
            $header .= \rtrim($line, "\r\n");
        } while ($header !== '');
        return $this;
    }
}
