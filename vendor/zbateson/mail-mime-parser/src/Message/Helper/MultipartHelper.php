<?php

namespace ZBateson\MailMimeParser\Message\Helper;

use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\Message\Factory\IMimePartFactory;
use ZBateson\MailMimeParser\Message\Factory\IUUEncodedPartFactory;
use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Message\PartFilter;

class MultipartHelper extends AbstractHelper
{
    private $genericHelper;

    public function __construct(IMimePartFactory $mimePartFactory, IUUEncodedPartFactory $uuEncodedPartFactory, GenericHelper $genericHelper)
    {
        parent::__construct($mimePartFactory, $uuEncodedPartFactory);
        $this->genericHelper = $genericHelper;
    }

    public function getUniqueBoundary(string $mimeType) : string
    {
        $type = \ltrim(\strtoupper(\preg_replace('/^(multipart\/(.{3}).*|.*)$/i', '$2-', $mimeType)), '-');
        return \uniqid('----=MMP-' . $type . '-', true);
    }

    public function setMimeHeaderBoundaryOnPart(IMimePart $part, string $mimeType) : self
    {
        $part->setRawHeader(
            HeaderConsts::CONTENT_TYPE,
            "$mimeType;\r\n\tboundary=\""
                . $this->getUniqueBoundary($mimeType) . '"'
        );
        $part->notify();
        return $this;
    }

    public function setMessageAsMixed(IMessage $message) : self
    {
        if ($message->hasContent()) {
            $part = $this->genericHelper->createNewContentPartFrom($message);
            $message->addChild($part, 0);
        }
        $this->setMimeHeaderBoundaryOnPart($message, 'multipart/mixed');
        $atts = $message->getAllAttachmentParts();
        if (!empty($atts)) {
            foreach ($atts as $att) {
                $att->notify();
            }
        }
        return $this;
    }

    public function setMessageAsAlternative(IMessage $message) : self
    {
        if ($message->hasContent()) {
            $part = $this->genericHelper->createNewContentPartFrom($message);
            $message->addChild($part, 0);
        }
        $this->setMimeHeaderBoundaryOnPart($message, 'multipart/alternative');
        return $this;
    }

    public function getContentPartContainerFromAlternative($mimeType, IMimePart $alternativePart)
    {
        $part = $alternativePart->getPart(0, PartFilter::fromInlineContentType($mimeType));
        $contPart = null;
        do {
            if ($part === null) {
                return false;
            }
            $contPart = $part;
            $part = $part->getParent();
        } while ($part !== $alternativePart);
        return $contPart;
    }

    public function removeAllContentPartsFromAlternative(IMessage $message, string $mimeType, IMimePart $alternativePart, bool $keepOtherContent) : bool
    {
        $rmPart = $this->getContentPartContainerFromAlternative($mimeType, $alternativePart);
        if ($rmPart === false) {
            return false;
        }
        if ($keepOtherContent && $rmPart->getChildCount() > 0) {
            $this->moveAllNonMultiPartsToMessageExcept($message, $rmPart, $mimeType);
            $alternativePart = $message->getPart(0, PartFilter::fromInlineContentType('multipart/alternative'));
        }
        $message->removePart($rmPart);
        if ($alternativePart !== null) {
            if ($alternativePart->getChildCount() === 1) {
                $this->genericHelper->replacePart($message, $alternativePart, $alternativePart->getChild(0));
            } elseif ($alternativePart->getChildCount() === 0) {
                $message->removePart($alternativePart);
            }
        }
        while ($message->getChildCount() === 1) {
            $this->genericHelper->replacePart($message, $message, $message->getChild(0));
        }
        return true;
    }

    public function createAlternativeContentPart(IMessage $message, IMessagePart $contentPart)
    {
        $altPart = $this->mimePartFactory->newInstance();
        $this->setMimeHeaderBoundaryOnPart($altPart, 'multipart/alternative');
        $message->removePart($contentPart);
        $message->addChild($altPart, 0);
        $altPart->addChild($contentPart, 0);
        return $altPart;
    }

    public function moveAllNonMultiPartsToMessageExcept(IMessage $message, IMimePart $from, string $exceptMimeType) : self
    {
        $parts = $from->getAllParts(function(IMessagePart $part) use ($exceptMimeType) {
            if ($part instanceof IMimePart && $part->isMultiPart()) {
                return false;
            }
            return \strcasecmp($part->getContentType(), $exceptMimeType) !== 0;
        });
        if (\strcasecmp($message->getContentType(), 'multipart/mixed') !== 0) {
            $this->setMessageAsMixed($message);
        }
        foreach ($parts as $key => $part) {
            $from->removePart($part);
            $message->addChild($part);
        }
        return $this;
    }

    public function enforceMime(IMessage $message)
    {
        if (!$message->isMime()) {
            if ($message->getAttachmentCount()) {
                $this->setMessageAsMixed($message);
            } else {
                $message->setRawHeader(HeaderConsts::CONTENT_TYPE, "text/plain;\r\n\tcharset=\"iso-8859-1\"");
            }
            $message->setRawHeader(HeaderConsts::MIME_VERSION, '1.0');
        }
    }

    public function createMultipartRelatedPartForInlineChildrenOf(IMimePart $parent)
    {
        $relatedPart = $this->mimePartFactory->newInstance();
        $this->setMimeHeaderBoundaryOnPart($relatedPart, 'multipart/related');
        foreach ($parent->getChildParts(PartFilter::fromDisposition('inline')) as $part) {
            $parent->removePart($part);
            $relatedPart->addChild($part);
        }
        $parent->addChild($relatedPart, 0);
        return $relatedPart;
    }

    public function findOtherContentPartFor(IMessage $message, string $mimeType)
    {
        $altPart = $message->getPart(
            0,
            PartFilter::fromInlineContentType(($mimeType === 'text/plain') ? 'text/html' : 'text/plain')
        );
        if ($altPart !== null && $altPart->getParent() !== null && $altPart->getParent()->isMultiPart()) {
            $altPartParent = $altPart->getParent();
            if ($altPartParent->getChildCount(PartFilter::fromDisposition('inline')) !== 1) {
                $altPart = $this->createMultipartRelatedPartForInlineChildrenOf($altPartParent);
            }
        }
        return $altPart;
    }

    public function createContentPartForMimeType(IMessage $message, string $mimeType, string $charset)
    {
        $mimePart = $this->mimePartFactory->newInstance();
        $mimePart->setRawHeader(HeaderConsts::CONTENT_TYPE, "$mimeType;\r\n\tcharset=\"$charset\"");
        $mimePart->setRawHeader(HeaderConsts::CONTENT_TRANSFER_ENCODING, 'quoted-printable');

        $this->enforceMime($message);
        $altPart = $this->findOtherContentPartFor($message, $mimeType);

        if ($altPart === $message) {
            $this->setMessageAsAlternative($message);
            $message->addChild($mimePart);
        } elseif ($altPart !== null) {
            $mimeAltPart = $this->createAlternativeContentPart($message, $altPart);
            $mimeAltPart->addChild($mimePart, 1);
        } else {
            $message->addChild($mimePart, 0);
        }

        return $mimePart;
    }

    public function createAndAddPartForAttachment(IMessage $message, $resource, string $mimeType, string $disposition, ?string $filename = null, string $encoding = 'base64')
    {
        if ($filename === null) {
            $filename = 'file' . \uniqid();
        }

        $safe = \iconv('UTF-8', 'US-ASCII//translit//ignore', $filename);
        if ($message->isMime()) {
            $part = $this->mimePartFactory->newInstance();
            $part->setRawHeader(HeaderConsts::CONTENT_TRANSFER_ENCODING, $encoding);
            if (\strcasecmp($message->getContentType(), 'multipart/mixed') !== 0) {
                $this->setMessageAsMixed($message);
            }
            $part->setRawHeader(HeaderConsts::CONTENT_TYPE, "$mimeType;\r\n\tname=\"$safe\"");
            $part->setRawHeader(HeaderConsts::CONTENT_DISPOSITION, "$disposition;\r\n\tfilename=\"$safe\"");
        } else {
            $part = $this->uuEncodedPartFactory->newInstance();
            $part->setFilename($safe);
        }
        $part->setContent($resource);
        $message->addChild($part);
    }

    public function removeAllContentPartsByMimeType(IMessage $message, string $mimeType, bool $keepOtherContent = false) : bool
    {
        $alt = $message->getPart(0, PartFilter::fromInlineContentType('multipart/alternative'));
        if ($alt !== null) {
            return $this->removeAllContentPartsFromAlternative($message, $mimeType, $alt, $keepOtherContent);
        }
        $message->removeAllParts(PartFilter::fromInlineContentType($mimeType));
        return true;
    }

    public function removePartByMimeType(IMessage $message, string $mimeType, int $index = 0) : bool
    {
        $parts = $message->getAllParts(PartFilter::fromInlineContentType($mimeType));
        $alt = $message->getPart(0, PartFilter::fromInlineContentType('multipart/alternative'));
        if ($parts === null || !isset($parts[$index])) {
            return false;
        } elseif (\count($parts) === 1) {
            return $this->removeAllContentPartsByMimeType($message, $mimeType, true);
        }
        $part = $parts[$index];
        $message->removePart($part);
        if ($alt !== null && $alt->getChildCount() === 1) {
            $this->genericHelper->replacePart($message, $alt, $alt->getChild(0));
        }
        return true;
    }

    public function setContentPartForMimeType(IMessage $message, string $mimeType, $stringOrHandle, string $charset) : self
    {
        $part = ($mimeType === 'text/html') ? $message->getHtmlPart() : $message->getTextPart();
        if ($part === null) {
            $part = $this->createContentPartForMimeType($message, $mimeType, $charset);
        } else {
            $contentType = $part->getContentType();
            $part->setRawHeader(HeaderConsts::CONTENT_TYPE, "$contentType;\r\n\tcharset=\"$charset\"");
        }
        $part->setContent($stringOrHandle);
        return $this;
    }
}
