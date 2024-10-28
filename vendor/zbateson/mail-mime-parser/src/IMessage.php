<?php

namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Message\IMimePart;

interface IMessage extends IMimePart
{
    public function getTextPart($index = 0);

    public function getTextPartCount();

    public function getHtmlPart($index = 0);

    public function getHtmlPartCount();

    public function getTextStream($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET);

    public function getTextContent($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET);

    public function getHtmlStream($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET);

    public function getHtmlContent($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET);

    public function setTextPart($resource, string $contentTypeCharset = 'UTF-8');

    public function setHtmlPart($resource, string $contentTypeCharset = 'UTF-8');

    public function removeTextPart(int $index = 0) : bool;

    public function removeAllTextParts(bool $moveRelatedPartsBelowMessage = true) : bool;

    public function removeHtmlPart(int $index = 0) : bool;

    public function removeAllHtmlParts(bool $moveRelatedPartsBelowMessage = true) : bool;

    public function getAttachmentPart(int $index);

    public function getAllAttachmentParts();

    public function getAttachmentCount();

    public function addAttachmentPart($resource, string $mimeType, ?string $filename = null, string $disposition = 'attachment', string $encoding = 'base64');

    public function addAttachmentPartFromFile($filePath, string $mimeType, ?string $filename = null, string $disposition = 'attachment', string $encoding = 'base64');

    public function removeAttachmentPart(int $index);

    public function getSignedMessageStream();

    public function getSignedMessageAsString();

    public function getSignaturePart();

    public function setAsMultipartSigned(string $micalg, string $protocol);

    public function setSignature(string $body);
}
