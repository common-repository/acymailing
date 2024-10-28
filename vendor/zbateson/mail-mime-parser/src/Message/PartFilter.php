<?php

namespace ZBateson\MailMimeParser\Message;

abstract class PartFilter
{
    public static function fromAttachmentFilter()
    {
        return function(IMessagePart $part) {
            $type = $part->getContentType();
            $disp = $part->getContentDisposition();
            if (\in_array($type, ['text/plain', 'text/html']) && $disp !== null && \strcasecmp($disp, 'inline') === 0) {
                return false;
            }
            return !(($part instanceof IMimePart)
                && ($part->isMultiPart() || $part->isSignaturePart()));
        };
    }

    public static function fromHeaderValue($name, $value, $excludeSignedParts = true)
    {
        return function(IMessagePart $part) use ($name, $value, $excludeSignedParts) {
            if ($part instanceof IMimePart) {
                if ($excludeSignedParts && $part->isSignaturePart()) {
                    return false;
                }
                return (\strcasecmp($part->getHeaderValue($name, ''), $value) === 0);
            }
            return false;
        };
    }

    public static function fromContentType($mimeType)
    {
        return function(IMessagePart $part) use ($mimeType) {
            return \strcasecmp($part->getContentType() ?: '', $mimeType) === 0;
        };
    }

    public static function fromInlineContentType($mimeType)
    {
        return function(IMessagePart $part) use ($mimeType) {
            $disp = $part->getContentDisposition();
            return (\strcasecmp($part->getContentType() ?: '', $mimeType) === 0) && ($disp === null
                || \strcasecmp($disp, 'attachment') !== 0);
        };
    }

    public static function fromDisposition($disposition, $includeMultipart = false, $includeSignedParts = false)
    {
        return function(IMessagePart $part) use ($disposition, $includeMultipart, $includeSignedParts) {
            if (($part instanceof IMimePart) && ((!$includeMultipart && $part->isMultiPart()) || (!$includeSignedParts && $part->isSignaturePart()))) {
                return false;
            }
            $disp = $part->getContentDisposition();
            return ($disp !== null && \strcasecmp($disp, $disposition) === 0);
        };
    }
}
